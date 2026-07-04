import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/models/api_response.dart';
import '../../services/ai_service.dart';
import '../../services/ai_socket_service.dart';
import '../../widgets/state_views.dart';

class AiChatPage extends StatefulWidget {
  const AiChatPage({super.key});

  @override
  State<AiChatPage> createState() => _AiChatPageState();
}

class _AiChatPageState extends State<AiChatPage> {
  final _inputController = TextEditingController();
  final _scrollController = ScrollController();

  AiService get _aiService => context.read<AiService>();
  AiSocketService get _aiSocket => context.read<AiSocketService>();

  bool _loadingHistory = true;
  bool _sending = false;
  String? _error;
  int? _conversationId;
  final List<_ChatBubble> _messages = [];

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  @override
  void dispose() {
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
        final latest = conversations.first;
        _conversationId = latest.id;
        final detail = await _aiService.getConversation(latest.id);
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
      }
    } catch (e) {
      _error = _formatError(e);
    } finally {
      if (mounted) {
        setState(() => _loadingHistory = false);
        _scrollToBottom();
      }
      // Conecta el socket en segundo plano para que el primer mensaje ya
      // pueda usar streaming; si falla, el envío caerá al respaldo REST.
      unawaited(_aiSocket.connect());
    }
  }

  Future<void> _send() async {
    final text = _inputController.text.trim();
    if (text.isEmpty || _sending) return;

    setState(() {
      _sending = true;
      _error = null;
      _messages.add(_ChatBubble(isUser: true, text: text));
    });
    _inputController.clear();
    _scrollToBottom();

    // Preferir streaming por WebSocket; si no hay conexión, usar REST.
    final connected = await _aiSocket.connect();
    if (connected) {
      await _sendStreaming(text);
    } else {
      await _sendRest(text);
    }
  }

  /// Envío en tiempo real: la respuesta se va escribiendo token a token en una
  /// burbuja que se actualiza con cada fragmento.
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
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = _formatError(e);
          // Quita la burbuja del asistente (vacía o incompleta) y reingresa el
          // texto del socio para que pueda reintentar.
          if (_messages.isNotEmpty && !_messages.last.isUser) {
            _messages.removeLast();
          }
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

  /// Respaldo REST: se usa cuando el socket no pudo conectarse.
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
        });
        _scrollToBottom();
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

  @override
  Widget build(BuildContext context) {
    if (_loadingHistory) {
      return const Center(child: CircularProgressIndicator());
    }

    return Column(
      children: [
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
                              ? Theme.of(context).colorScheme.primaryContainer
                              : Theme.of(context).colorScheme.surfaceContainerHighest,
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
              backgroundColor: Theme.of(context).colorScheme.errorContainer,
              actions: [
                TextButton(
                  onPressed: () => setState(() => _error = null),
                  child: const Text('Cerrar'),
                ),
              ],
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
                    decoration: const InputDecoration(
                      hintText: 'Escribe tu pregunta...',
                      border: OutlineInputBorder(),
                      isDense: true,
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton.filled(
                  onPressed: _sending ? null : _send,
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
  // Mutable: durante el streaming se le van concatenando los fragmentos.
  String text;
}
