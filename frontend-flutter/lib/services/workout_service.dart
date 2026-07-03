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
}
