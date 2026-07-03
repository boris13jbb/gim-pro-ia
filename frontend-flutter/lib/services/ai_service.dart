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
    this.lastMessage,
  });

  final int id;
  final String? title;
  final AiMessage? lastMessage;

  factory AiConversationSummary.fromJson(Map<String, dynamic> json) {
    return AiConversationSummary(
      id: _requireInt(json['id'], 'id'),
      title: json['title'] as String?,
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
  });

  final int id;
  final String? title;
  final List<AiMessage> messages;

  factory AiConversationDetail.fromJson(Map<String, dynamic> json) {
    final raw = json['messages'] as List<dynamic>? ?? [];
    return AiConversationDetail(
      id: _requireInt(json['id'], 'id'),
      title: json['title'] as String?,
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

class AiService {
  AiService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<List<AiConversationSummary>> listConversations() {
    return _apiClient.getData(
      '/ai/conversations',
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
}
