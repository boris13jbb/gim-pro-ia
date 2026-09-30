import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/models/api_response.dart';
import '../../services/ai_service.dart';
import '../../services/ai_socket_service.dart';
import '../../services/ai_voice_service.dart';
import '../../widgets/state_views.dart';

class AiChatPage extends StatefulWidget {
  const AiChatPage({super.key});

  @override
  State<AiChatPage> createState() => _AiChatPageState();
}

class _AiChatPageState extends State<AiChatPage> {
  final _inputController = TextEditingController();
  final _scrollController = ScrollController();
  final _voiceService = AiVoiceService();

  AiService get _aiService => context.read<AiService>();
  AiSocketService get _aiSocket => context.read<AiSocketService>();

  bool _loadingHistory = true;
  bool _sending = false;
  bool _managingConversation = false;
  bool _voiceCallActive = false;
  bool _voiceInitializing = false;
  bool _listening = false;
  bool _speaking = false;
  bool _voiceSubmitting = false;
  double _soundLevel = 0;
  String? _error;
  int? _conversationId;
  String? _conversationTitle;
  final List<_ChatBubble> _messages = [];

  bool get _hasActiveConversation =>
      _conversationId != null && _messages.isNotEmpty;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  @override
  void dispose() {
    unawaited(_endVoiceCall());
    _voiceService.dispose();
    _inputController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _bootstrap() async {
    setState(() {
      _loadingHistory = true;
      _error = null;
    });

    try {
      final conversations = await _aiService.listConversations();
      if (conversations.isNotEmpty) {
        await _openConversation(conversations.first.id);
      }
    } catch (e) {
      _error = _formatError(e);
    } finally {
      if (mounted) {
        setState(() => _loadingHistory = false);
        _scrollToBottom();
      }
      unawaited(_aiSocket.connect());
    }
  }

  Future<void> _openConversation(int id) async {
    final detail = await _aiService.getConversation(id);
    if (!mounted) return;
    setState(() {
      _conversationId = detail.id;
      _conversationTitle = detail.title;
      _messages
        ..clear()
        ..addAll(
          detail.messages.map(
            (m) => _ChatBubble(
              isUser: m.role == 'user',
              text: m.content,
            ),
          ),
        );
      _error = null;
    });
    _scrollToBottom();
  }

  void _newChat() {
    setState(() {
      _conversationId = null;
      _conversationTitle = null;
      _messages.clear();
      _error = null;
    });
    _inputController.clear();
  }

  Future<void> _archiveCurrentChat() async {
    final id = _conversationId;
    if (id == null) return;

    setState(() => _managingConversation = true);
    try {
      await _aiService.archiveConversation(id);
      if (!mounted) return;
      _newChat();
      _showSnack('Conversación archivada');
    } catch (e) {
      if (mounted) setState(() => _error = _formatError(e));
    } finally {
      if (mounted) setState(() => _managingConversation = false);
    }
  }

  Future<void> _deleteCurrentChat() async {
    final id = _conversationId;
    if (id == null) return;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Eliminar conversación'),
        content: const Text(
          'Se borrarán todos los mensajes de esta conversación. '
          'Esta acción no se puede deshacer.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Eliminar'),
          ),
        ],
      ),
    );

    if (confirmed != true || !mounted) return;

    setState(() => _managingConversation = true);
    try {
      await _aiService.deleteConversation(id);
      if (!mounted) return;
      _newChat();
      _showSnack('Conversación eliminada');
    } catch (e) {
      if (mounted) setState(() => _error = _formatError(e));
    } finally {
      if (mounted) setState(() => _managingConversation = false);
    }
  }

  Future<void> _showConversationsSheet({required bool archived}) async {
    try {
      final items = await _aiService.listConversations(
        status: archived ? 'archived' : 'active',
      );
      if (!mounted) return;

      await showModalBottomSheet<void>(
        context: context,
        showDragHandle: true,
        isScrollControlled: true,
        builder: (context) {
          return SafeArea(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          archived
                              ? 'Conversaciones archivadas'
                              : 'Mis conversaciones',
                          style: Theme.of(context).textTheme.titleMedium,
                        ),
                      ),
                      if (archived)
                        TextButton(
                          onPressed: () {
                            Navigator.pop(context);
                            _showConversationsSheet(archived: false);
                          },
                          child: const Text('Activas'),
                        )
                      else
                        TextButton(
                          onPressed: () {
                            Navigator.pop(context);
                            _showConversationsSheet(archived: true);
                          },
                          child: const Text('Archivadas'),
                        ),
                    ],
                  ),
                ),
                if (items.isEmpty)
                  Padding(
                    padding: const EdgeInsets.all(32),
                    child: Text(
                      archived
                          ? 'No tienes conversaciones archivadas.'
                          : 'Aún no tienes conversaciones guardadas.',
                      style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            color: Theme.of(context).colorScheme.onSurfaceVariant,
                          ),
                    ),
                  )
                else
                  Flexible(
                    child: ListView.separated(
                      shrinkWrap: true,
                      itemCount: items.length,
                      separatorBuilder: (_, _) => const Divider(height: 1),
                      itemBuilder: (context, index) {
                        final item = items[index];
                        final preview =
                            item.lastMessage?.content ?? item.title ?? 'Sin mensajes';
                        return ListTile(
                          leading: Icon(
                            archived
                                ? Icons.inventory_2_outlined
                                : Icons.chat_bubble_outline,
                          ),
                          title: Text(
                            item.title ?? 'Conversación #${item.id}',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          subtitle: Text(
                            preview,
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                          trailing: archived
                              ? IconButton(
                                  tooltip: 'Restaurar',
                                  icon: const Icon(Icons.unarchive_outlined),
                                  onPressed: () async {
                                    Navigator.pop(context);
                                    setState(() => _managingConversation = true);
                                    try {
                                      await _aiService.restoreConversation(item.id);
                                      if (!mounted) return;
                                      await _openConversation(item.id);
                                      _showSnack('Conversación restaurada');
                                    } catch (e) {
                                      if (mounted) {
                                        setState(() => _error = _formatError(e));
                                      }
                                    } finally {
                                      if (mounted) {
                                        setState(() => _managingConversation = false);
                                      }
                                    }
                                  },
                                )
                              : null,
                          onTap: () async {
                            Navigator.pop(context);
                            setState(() => _loadingHistory = true);
                            try {
                              await _openConversation(item.id);
                            } catch (e) {
                              if (mounted) setState(() => _error = _formatError(e));
                            } finally {
                              if (mounted) setState(() => _loadingHistory = false);
                            }
                          },
                        );
                      },
                    ),
                  ),
              ],
            ),
          );
        },
      );
    } catch (e) {
      if (mounted) setState(() => _error = _formatError(e));
    }
  }

  void _showSnack(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _send({String? message}) async {
    final text = (message ?? _inputController.text).trim();
    if (text.isEmpty || _sending || _managingConversation) return;

    await _voiceService.stopListening();
    if (mounted) setState(() => _listening = false);

    setState(() {
      _sending = true;
      _error = null;
      _messages.add(_ChatBubble(isUser: true, text: text));
    });
    _inputController.clear();
    _scrollToBottom();

    final connected = await _aiSocket.connect();
    if (connected) {
      await _sendStreaming(text);
    } else {
      await _sendRest(text);
    }
  }

  Future<void> _sendStreaming(String text) async {
    final assistantBubble = _ChatBubble(isUser: false, text: '');
    setState(() => _messages.add(assistantBubble));
    _scrollToBottom();

    try {
      final conversationId = await _aiSocket.sendMessage(
        message: text,
        conversationId: _conversationId,
        onChunk: (delta) {
          if (!mounted) return;
          setState(() => assistantBubble.text += delta);
          _scrollToBottom();
        },
      );
      _conversationId = conversationId;
      if (_conversationTitle == null && mounted) {
        setState(() {
          _conversationTitle = text.length <= 60
              ? text
              : '${text.substring(0, 57)}...';
        });
      }
      if (_voiceCallActive && assistantBubble.text.trim().isNotEmpty) {
        await _speakAssistantReply(assistantBubble.text);
      } else if (_voiceCallActive && mounted) {
        unawaited(_startVoiceListening());
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = _formatError(e);
          if (_messages.isNotEmpty && !_messages.last.isUser) {
            _messages.removeLast();
          }
          if (_messages.isNotEmpty && _messages.last.isUser) {
            _messages.removeLast();
          }
          _inputController.text = text;
        });
      }
      if (_voiceCallActive && mounted) {
        unawaited(_startVoiceListening());
      }
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  Future<void> _sendRest(String text) async {
    try {
      final result = await _aiService.sendMessage(
        message: text,
        conversationId: _conversationId,
      );
      _conversationId = result.conversationId;
      if (mounted) {
        setState(() {
          _messages.add(_ChatBubble(isUser: false, text: result.reply));
          _conversationTitle ??=
              text.length <= 60 ? text : '${text.substring(0, 57)}...';
        });
        _scrollToBottom();
        if (_voiceCallActive && result.reply.trim().isNotEmpty) {
          await _speakAssistantReply(result.reply);
        } else if (_voiceCallActive && mounted) {
          unawaited(_startVoiceListening());
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = _formatError(e);
          if (_messages.isNotEmpty && _messages.last.isUser) {
            _messages.removeLast();
          }
          _inputController.text = text;
        });
      }
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  String _formatError(Object error) {
    if (error is ApiException) return error.message;
    if (error is FormatException) return error.message;
    return error.toString();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_scrollController.hasClients) return;
      _scrollController.animateTo(
        _scrollController.position.maxScrollExtent,
        duration: const Duration(milliseconds: 250),
        curve: Curves.easeOut,
      );
    });
  }

  Future<void> _toggleVoiceCall() async {
    if (_voiceCallActive) {
      await _endVoiceCall();
      return;
    }
    if (_sending || _managingConversation || _voiceInitializing) return;

    setState(() => _voiceInitializing = true);
    try {
      final ready = await _voiceService.initialize(aiService: _aiService);
      if (!ready) {
        if (mounted) {
          setState(() {
            _error = kIsWeb
                ? 'No se pudo activar el micrófono. Usa Chrome o Edge, permite el '
                    'micrófono en la barra de direcciones y recarga la página.'
                : 'No se pudo activar el micrófono. Ve a Ajustes > Apps > Iron Gym '
                    'y permite Micrófono.';
          });
        }
        return;
      }

      if (!mounted) return;
      setState(() {
        _voiceCallActive = true;
        _error = null;
      });
      if (_voiceService.usesServerPipeline) {
        _showSnack(
          'Voz local: Whisper → Ollama → Piper. Habla y toca Enviar.',
        );
        await _startServerRecording();
      } else {
        _showSnack('Llamada activa. Habla y haz una pausa corta para enviar.');
        await _startVoiceListening();
      }
    } finally {
      if (mounted) setState(() => _voiceInitializing = false);
    }
  }

  Future<void> _submitVoiceMessage(String text) async {
    final trimmed = text.trim();
    if (trimmed.isEmpty || _voiceSubmitting || !_voiceCallActive) return;

    _voiceSubmitting = true;
    try {
      await _voiceService.stopListening();
      if (!mounted) return;
      setState(() => _listening = false);
      await _send(message: trimmed);
    } finally {
      _voiceSubmitting = false;
    }
  }

  Future<void> _startServerRecording() async {
    if (!_voiceCallActive || _sending || _speaking || _voiceSubmitting || !mounted) {
      return;
    }
    await _voiceService.startRecording();
    if (mounted) setState(() => _listening = true);
  }

  Future<void> _processServerVoiceTurn() async {
    if (!_voiceCallActive || _voiceSubmitting) return;

    _voiceSubmitting = true;
    setState(() {
      _sending = true;
      _listening = false;
      _error = null;
    });

    try {
      final result = await _voiceService.sendRecording(
        conversationId: _conversationId,
      );
      if (result == null || !mounted) return;

      setState(() {
        _conversationId = result.conversationId;
        _messages.add(_ChatBubble(isUser: true, text: result.transcript));
        _messages.add(_ChatBubble(isUser: false, text: result.reply));
        _conversationTitle ??= result.transcript.length <= 60
            ? result.transcript
            : '${result.transcript.substring(0, 57)}...';
        _inputController.clear();
      });
      _scrollToBottom();

      setState(() => _speaking = true);
      if (result.audioBase64 != null && result.audioBase64!.isNotEmpty) {
        await _voiceService.playServerAudio(result.audioBase64!);
      } else {
        await _voiceService.speakLocal(result.reply);
      }
    } catch (e) {
      if (mounted) setState(() => _error = _formatError(e));
    } finally {
      _voiceSubmitting = false;
      if (mounted) {
        setState(() {
          _sending = false;
          _speaking = false;
        });
      }
      if (_voiceCallActive && mounted) {
        await _startServerRecording();
      }
    }
  }

  Future<void> _startVoiceListening() async {
    if (!_voiceCallActive || _sending || _speaking || _voiceSubmitting || !mounted) {
      return;
    }

    setState(() => _listening = true);
    final started = await _voiceService.startListening(
      onPartial: (partial) {
        if (!mounted || !_voiceCallActive) return;
        setState(() => _inputController.text = partial);
      },
      onFinal: (finalText) async {
        await _submitVoiceMessage(finalText);
      },
      onSoundLevel: (level) {
        if (!mounted || !_voiceCallActive) return;
        setState(() => _soundLevel = level);
      },
      onStatus: (status) async {
        // Web: al terminar la escucha, envía el texto parcial si no hubo finalResult.
        if (status != 'done' && status != 'notListening') return;
        if (!mounted || !_voiceCallActive || _voiceSubmitting || _sending) return;

        final pending = _inputController.text.trim();
        if (pending.isNotEmpty) {
          await _submitVoiceMessage(pending);
          return;
        }
        if (_voiceCallActive && !_sending && !_speaking && mounted) {
          setState(() => _listening = false);
          await _startVoiceListening();
        }
      },
      onError: (message) {
        if (!mounted) return;
        setState(() {
          _listening = false;
          _error = message;
        });
      },
    );

    if (!started && mounted) {
      setState(() => _listening = false);
    }
  }

  Future<void> _sendVoiceNow() async {
    if (!_voiceCallActive) return;
    if (_voiceService.usesServerPipeline) {
      await _processServerVoiceTurn();
      return;
    }
    final captured = await _voiceService.stopListeningCapture();
    final text = captured.isNotEmpty ? captured : _inputController.text.trim();
    await _submitVoiceMessage(text);
  }

  Future<void> _speakAssistantReply(String text) async {
    if (!_voiceCallActive || !mounted || _voiceService.usesServerPipeline) return;

    setState(() => _speaking = true);
    try {
      await _voiceService.speakLocal(text);
    } catch (_) {
      // Si TTS falla, la conversación de voz continúa escuchando.
    }
    if (!mounted) return;
    setState(() => _speaking = false);
    if (_voiceCallActive) {
      await _startVoiceListening();
    }
  }

  Future<void> _endVoiceCall() async {
    await _voiceService.stopRecording();
    await _voiceService.stopListening();
    await _voiceService.stopSpeaking();
    if (!mounted) return;
    setState(() {
      _voiceCallActive = false;
      _listening = false;
      _speaking = false;
    });
  }

  String _voiceStatusLabel() {
    if (_speaking) return 'El asistente está respondiendo...';
    if (_sending) return 'Procesando tu pregunta...';
    if (_listening) {
      if (_voiceService.usesServerPipeline) {
        return 'Grabando — habla y toca Enviar';
      }
      final draft = _inputController.text.trim();
      if (draft.isNotEmpty) {
        return 'Escuchando: "$draft"';
      }
      if (_soundLevel > 2) {
        return 'Te escucho… sigue hablando';
      }
      return 'Escuchando — habla cerca del micrófono';
    }
    return 'Llamada de voz activa';
  }

  Future<void> _onMenuSelected(String value) async {
    switch (value) {
      case 'new':
        _newChat();
      case 'history':
        await _showConversationsSheet(archived: false);
      case 'archive':
        await _archiveCurrentChat();
      case 'delete':
        await _deleteCurrentChat();
      case 'archived':
        await _showConversationsSheet(archived: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loadingHistory) {
      return const Center(child: CircularProgressIndicator());
    }

    final theme = Theme.of(context);

    return Column(
      children: [
        Material(
          elevation: 0,
          color: theme.colorScheme.surfaceContainerLow,
          child: SafeArea(
            bottom: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(8, 4, 8, 4),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _conversationTitle ?? 'Nueva conversación',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: theme.textTheme.titleSmall,
                        ),
                        if (_conversationId != null)
                          Text(
                            'Conversación #$_conversationId',
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: theme.colorScheme.onSurfaceVariant,
                            ),
                          ),
                      ],
                    ),
                  ),
                  PopupMenuButton<String>(
                    tooltip: 'Opciones del chat',
                    enabled: !_sending && !_managingConversation,
                    onSelected: _onMenuSelected,
                    itemBuilder: (context) => [
                      const PopupMenuItem(
                        value: 'new',
                        child: ListTile(
                          leading: Icon(Icons.add_comment_outlined),
                          title: Text('Nuevo chat'),
                          contentPadding: EdgeInsets.zero,
                        ),
                      ),
                      const PopupMenuItem(
                        value: 'history',
                        child: ListTile(
                          leading: Icon(Icons.history),
                          title: Text('Mis conversaciones'),
                          contentPadding: EdgeInsets.zero,
                        ),
                      ),
                      if (_hasActiveConversation)
                        const PopupMenuItem(
                          value: 'archive',
                          child: ListTile(
                            leading: Icon(Icons.archive_outlined),
                            title: Text('Archivar chat'),
                            contentPadding: EdgeInsets.zero,
                          ),
                        ),
                      if (_hasActiveConversation)
                        const PopupMenuItem(
                          value: 'delete',
                          child: ListTile(
                            leading: Icon(Icons.delete_outline),
                            title: Text('Eliminar chat'),
                            contentPadding: EdgeInsets.zero,
                          ),
                        ),
                      const PopupMenuItem(
                        value: 'archived',
                        child: ListTile(
                          leading: Icon(Icons.inventory_2_outlined),
                          title: Text('Ver archivadas'),
                          contentPadding: EdgeInsets.zero,
                        ),
                      ),
                    ],
                    icon: _managingConversation
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.more_vert),
                  ),
                ],
              ),
            ),
          ),
        ),
        Expanded(
          child: _messages.isEmpty
              ? const EmptyState(
                  title: 'Asistente Iron Gym',
                  subtitle:
                      'Pregunta sobre tu membresía, asistencias, progreso o rutina. '
                      'Usaré solo tus datos reales del gimnasio.',
                )
              : ListView.builder(
                  controller: _scrollController,
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                  itemCount: _messages.length,
                  itemBuilder: (context, index) {
                    final bubble = _messages[index];
                    return Align(
                      alignment: bubble.isUser
                          ? Alignment.centerRight
                          : Alignment.centerLeft,
                      child: Container(
                        margin: const EdgeInsets.only(bottom: 10),
                        padding: const EdgeInsets.symmetric(
                          horizontal: 14,
                          vertical: 10,
                        ),
                        constraints: BoxConstraints(
                          maxWidth: MediaQuery.sizeOf(context).width * 0.82,
                        ),
                        decoration: BoxDecoration(
                          color: bubble.isUser
                              ? theme.colorScheme.primaryContainer
                              : theme.colorScheme.surfaceContainerHighest,
                          borderRadius: BorderRadius.circular(14),
                        ),
                        child: Text(bubble.text),
                      ),
                    );
                  },
                ),
        ),
        if (_error != null)
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: MaterialBanner(
              content: Text(_error!),
              leading: const Icon(Icons.error_outline),
              backgroundColor: theme.colorScheme.errorContainer,
              actions: [
                TextButton(
                  onPressed: () => setState(() => _error = null),
                  child: const Text('Cerrar'),
                ),
              ],
            ),
          ),
        if (_voiceCallActive)
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 0, 12, 4),
            child: Material(
              color: theme.colorScheme.primaryContainer,
              borderRadius: BorderRadius.circular(12),
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                child: Row(
                  children: [
                    Icon(
                      _listening ? Icons.mic : Icons.volume_up_outlined,
                      color: theme.colorScheme.primary,
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        _voiceStatusLabel(),
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: theme.colorScheme.onPrimaryContainer,
                        ),
                      ),
                    ),
                    if (_listening ||
                        _inputController.text.trim().isNotEmpty)
                      TextButton(
                        onPressed: (_sending || _voiceSubmitting)
                            ? null
                            : _sendVoiceNow,
                        child: const Text('Enviar'),
                      ),
                    if (_listening)
                      SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: theme.colorScheme.primary,
                        ),
                      ),
                  ],
                ),
              ),
            ),
          ),
        SafeArea(
          top: false,
          child: Padding(
            padding: EdgeInsets.fromLTRB(
              12,
              8,
              12,
              8 + MediaQuery.viewInsetsOf(context).bottom,
            ),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _inputController,
                    minLines: 1,
                    maxLines: 4,
                    textInputAction: TextInputAction.send,
                    onSubmitted: (_) => _send(),
                    decoration: InputDecoration(
                      hintText: _voiceCallActive
                          ? 'Habla o escribe tu pregunta...'
                          : 'Escribe tu pregunta...',
                      border: const OutlineInputBorder(),
                      isDense: true,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton.filledTonal(
                  tooltip: _voiceCallActive
                      ? 'Finalizar llamada de voz'
                      : 'Iniciar llamada de voz',
                  style: IconButton.styleFrom(
                    backgroundColor: _voiceCallActive
                        ? theme.colorScheme.errorContainer
                        : null,
                    foregroundColor: _voiceCallActive
                        ? theme.colorScheme.onErrorContainer
                        : null,
                  ),
                  onPressed: (_sending || _managingConversation || _voiceInitializing)
                      ? null
                      : _toggleVoiceCall,
                  icon: _voiceInitializing
                      ? SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2,
                            color: theme.colorScheme.onSecondaryContainer,
                          ),
                        )
                      : Icon(
                          _voiceCallActive ? Icons.call_end : Icons.phone_in_talk,
                        ),
                ),
                const SizedBox(width: 4),
                IconButton.filled(
                  onPressed: (_sending || _managingConversation) ? null : _send,
                  icon: _sending
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.send),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class _ChatBubble {
  _ChatBubble({required this.isUser, required this.text});

  final bool isUser;
  String text;
}
