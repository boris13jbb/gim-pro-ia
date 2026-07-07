import 'member_user.dart';

class PageMeta {
  PageMeta({
    required this.page,
    required this.limit,
    required this.total,
    required this.totalPages,
  });

  final int page;
  final int limit;
  final int total;
  final int totalPages;

  bool get hasNextPage => page < totalPages;

  factory PageMeta.fromJson(Map<String, dynamic> json) {
    return PageMeta(
      page: json['page'] as int? ?? 1,
      limit: json['limit'] as int? ?? 20,
      total: json['total'] as int? ?? 0,
      totalPages: json['totalPages'] as int? ?? 0,
    );
  }
}

class PagedMembers {
  PagedMembers({required this.items, required this.meta});

  final List<MemberUser> items;
  final PageMeta meta;

  factory PagedMembers.fromJson(Map<String, dynamic> json) {
    final rawItems = json['items'] as List? ?? [];
    return PagedMembers(
      items: rawItems
          .whereType<Map<String, dynamic>>()
          .map(MemberUser.fromJson)
          .toList(),
      meta: PageMeta.fromJson(json['meta'] as Map<String, dynamic>? ?? {}),
    );
  }
}
