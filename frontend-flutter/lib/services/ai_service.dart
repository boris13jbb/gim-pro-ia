import 'package:dio/dio.dart';

import 'api_client.dart';

int _requireInt(dynamic value, String field) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) {
    final parsed = int.tryParse(value);
    if (parsed != null) return parsed;
  }
  throw FormatException('Campo $field inválido en respuesta de IA');
}

class AiMessage {
  AiMessage({
    required this.id,
    required this.role,
    required this.content,
    this.createdAt,
  });

  final int id;
  final String role;
  final String content;
  final DateTime? createdAt;

  factory AiMessage.fromJson(Map<String, dynamic> json) {
    return AiMessage(
      id: _requireInt(json['id'], 'id'),
      role: json['role']?.toString() ?? 'assistant',
      content: json['content']?.toString() ?? '',
      createdAt: json['createdAt'] != null
          ? DateTime.tryParse(json['createdAt'].toString())
          : null,
    );
  }
}

class AiConversationSummary {
  AiConversationSummary({
    required this.id,
    this.title,
    this.status = 'active',
    this.lastMessage,
  });

  final int id;
  final String? title;
  final String status;
  final AiMessage? lastMessage;

  factory AiConversationSummary.fromJson(Map<String, dynamic> json) {
    return AiConversationSummary(
      id: _requireInt(json['id'], 'id'),
      title: json['title'] as String?,
      status: json['status']?.toString() ?? 'active',
      lastMessage: json['lastMessage'] != null
          ? AiMessage.fromJson(
              Map<String, dynamic>.from(json['lastMessage'] as Map),
            )
          : null,
    );
  }
}

class AiConversationDetail {
  AiConversationDetail({
    required this.id,
    required this.messages,
    this.title,
    this.status = 'active',
  });

  final int id;
  final String? title;
  final String status;
  final List<AiMessage> messages;

  factory AiConversationDetail.fromJson(Map<String, dynamic> json) {
    final raw = json['messages'] as List<dynamic>? ?? [];
    return AiConversationDetail(
      id: _requireInt(json['id'], 'id'),
      title: json['title'] as String?,
      status: json['status']?.toString() ?? 'active',
      messages: raw
          .map((e) => AiMessage.fromJson(Map<String, dynamic>.from(e as Map)))
          .toList(),
    );
  }
}

class AiChatResult {
  AiChatResult({required this.conversationId, required this.reply});

  final int conversationId;
  final String reply;

  factory AiChatResult.fromJson(Map<String, dynamic> json) {
    final reply = json['reply']?.toString();
    if (reply == null || reply.isEmpty) {
      throw const FormatException('El asistente no devolvió una respuesta');
    }

    return AiChatResult(
      conversationId: _requireInt(json['conversationId'], 'conversationId'),
      reply: reply,
    );
  }
}

class AiVoiceStatus {
  AiVoiceStatus({
    required this.enabled,
    required this.provider,
    required this.sttReady,
    required this.ttsReady,
  });

  final bool enabled;
  final String provider;
  final bool sttReady;
  final bool ttsReady;

  factory AiVoiceStatus.fromJson(Map<String, dynamic> json) {
    final stt = json['stt'] as Map? ?? {};
    final tts = json['tts'] as Map? ?? {};
    return AiVoiceStatus(
      enabled: json['enabled'] == true,
      provider: json['provider']?.toString() ?? 'ollama',
      sttReady: stt['ready'] == true,
      ttsReady: tts['ready'] == true,
    );
  }
}

class AiVoiceTurnResult {
  AiVoiceTurnResult({
    required this.conversationId,
    required this.transcript,
    required this.reply,
    this.audioBase64,
    this.audioMimeType,
  });

  final int conversationId;
  final String transcript;
  final String reply;
  final String? audioBase64;
  final String? audioMimeType;

  factory AiVoiceTurnResult.fromJson(Map<String, dynamic> json) {
    return AiVoiceTurnResult(
      conversationId: _requireInt(json['conversationId'], 'conversationId'),
      transcript: json['transcript']?.toString() ?? '',
      reply: json['reply']?.toString() ?? '',
      audioBase64: json['audioBase64'] as String?,
      audioMimeType: json['audioMimeType'] as String?,
    );
  }
}

class AiService {
  AiService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<List<AiConversationSummary>> listConversations({
    String status = 'active',
  }) {
    return _apiClient.getData(
      '/ai/conversations',
      query: {'status': status},
      parser: (raw) {
        final list = raw as List<dynamic>;
        return list
            .map(
              (e) => AiConversationSummary.fromJson(
                Map<String, dynamic>.from(e as Map),
              ),
            )
            .toList();
      },
    );
  }

  Future<AiConversationDetail> getConversation(int id) {
    return _apiClient.getData(
      '/ai/conversations/$id',
      parser: (raw) =>
          AiConversationDetail.fromJson(Map<String, dynamic>.from(raw as Map)),
    );
  }

  Future<AiChatResult> sendMessage({
    required String message,
    int? conversationId,
  }) {
    return _apiClient.postData(
      '/ai/chat',
      body: {
        'message': message,
        if (conversationId != null) 'conversationId': conversationId,
      },
      parser: (raw) =>
          AiChatResult.fromJson(Map<String, dynamic>.from(raw as Map)),
    );
  }

  Future<void> archiveConversation(int id) {
    return _updateConversationStatus(id, 'archived');
  }

  Future<void> restoreConversation(int id) {
    return _updateConversationStatus(id, 'active');
  }

  Future<void> deleteConversation(int id) {
    return _apiClient.deleteData<Object?>(
      '/ai/conversations/$id',
      parser: (_) => null,
    );
  }

  Future<void> _updateConversationStatus(int id, String status) {
    return _apiClient.patchData<Object?>(
      '/ai/conversations/$id/status',
      body: {'status': status},
      parser: (_) => null,
    );
  }

  Future<AiVoiceStatus> getVoiceStatus() {
    return _apiClient.getData(
      '/ai/voice/status',
      parser: (raw) =>
          AiVoiceStatus.fromJson(Map<String, dynamic>.from(raw as Map)),
    );
  }

  Future<AiVoiceTurnResult> sendVoiceTurn({
    required String filePath,
    int? conversationId,
  }) {
    final form = FormData.fromMap({
      'audio': MultipartFile.fromFileSync(filePath, filename: 'voice.wav'),
      if (conversationId != null) 'conversationId': conversationId,
    });

    return _apiClient.postMultipart(
      '/ai/voice/turn',
      formData: form,
      receiveTimeout: const Duration(seconds: 180),
      sendTimeout: const Duration(seconds: 60),
      parser: (raw) =>
          AiVoiceTurnResult.fromJson(Map<String, dynamic>.from(raw as Map)),
    );
  }
}
