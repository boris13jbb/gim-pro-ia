import '../core/models/workout_routine.dart';
import 'api_client.dart';

class WorkoutService {
  WorkoutService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<WorkoutRoutineResponse> fetchCurrentRoutine() {
    return _apiClient.getData(
      '/workout-routines/me/current',
      parser: (raw) =>
          WorkoutRoutineResponse.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<WorkoutRoutineResponse> fetchMemberCurrentRoutine(int memberId) {
    return _apiClient.getData(
      '/workout-routines/members/$memberId/current',
      parser: (raw) =>
          WorkoutRoutineResponse.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<WorkoutRoutine> assignMemberRoutine({
    required int memberId,
    String? day1,
    String? day2,
    String? day3,
    String? day4,
    String? day5,
    String? day6,
    String? notes,
  }) {
    return _apiClient.postData(
      '/workout-routines/members/$memberId',
      body: {
        if (day1 != null && day1.isNotEmpty) 'day1': day1,
        if (day2 != null && day2.isNotEmpty) 'day2': day2,
        if (day3 != null && day3.isNotEmpty) 'day3': day3,
        if (day4 != null && day4.isNotEmpty) 'day4': day4,
        if (day5 != null && day5.isNotEmpty) 'day5': day5,
        if (day6 != null && day6.isNotEmpty) 'day6': day6,
        if (notes != null && notes.isNotEmpty) 'notes': notes,
      },
      parser: (raw) => WorkoutRoutine.fromJson(raw as Map<String, dynamic>),
    );
  }
}
