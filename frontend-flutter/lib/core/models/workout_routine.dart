class WorkoutRoutine {
  WorkoutRoutine({
    required this.id,
    required this.memberId,
    this.day1,
    this.day2,
    this.day3,
    this.day4,
    this.day5,
    this.day6,
    this.notes,
    this.assignedAt,
  });

  final int id;
  final int memberId;
  final String? day1;
  final String? day2;
  final String? day3;
  final String? day4;
  final String? day5;
  final String? day6;
  final String? notes;
  final DateTime? assignedAt;

  List<WorkoutDay> get days {
    final entries = <WorkoutDay>[
      WorkoutDay(label: 'Día 1', content: day1),
      WorkoutDay(label: 'Día 2', content: day2),
      WorkoutDay(label: 'Día 3', content: day3),
      WorkoutDay(label: 'Día 4', content: day4),
      WorkoutDay(label: 'Día 5', content: day5),
      WorkoutDay(label: 'Día 6', content: day6),
    ];
    return entries
        .where((day) => day.content != null && day.content!.trim().isNotEmpty)
        .toList();
  }

  factory WorkoutRoutine.fromJson(Map<String, dynamic> json) {
    return WorkoutRoutine(
      id: json['id'] as int,
      memberId: json['memberId'] as int,
      day1: json['day1']?.toString(),
      day2: json['day2']?.toString(),
      day3: json['day3']?.toString(),
      day4: json['day4']?.toString(),
      day5: json['day5']?.toString(),
      day6: json['day6']?.toString(),
      notes: json['notes']?.toString(),
      assignedAt: json['assignedAt'] != null
          ? DateTime.tryParse(json['assignedAt'].toString())
          : null,
    );
  }
}

class WorkoutRoutineResponse {
  WorkoutRoutineResponse({required this.memberId, this.current});

  final int memberId;
  final WorkoutRoutine? current;

  factory WorkoutRoutineResponse.fromJson(Map<String, dynamic> json) {
    final current = json['current'];
    return WorkoutRoutineResponse(
      memberId: json['memberId'] as int,
      current: current is Map<String, dynamic>
          ? WorkoutRoutine.fromJson(current)
          : null,
    );
  }
}

class WorkoutDay {
  WorkoutDay({required this.label, this.content});

  final String label;
  final String? content;
}
