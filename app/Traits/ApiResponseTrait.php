<?php
// app/Traits/ApiResponseTrait.php
namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response;

trait ApiResponseTrait
{
    protected function successResponse($data = null, $message = null, $statusCode = 200): JsonResponse
    {
        $locale = request()->attributes->get('locale', 'ar');

        if (is_string($message)) {
            $message = ['ar' => $message, 'en' => $message];
        }

        $response = [
            'success' => true,
            'status_code' => $statusCode,
            'message' => $message[$locale] ?? ($locale === 'ar' ? 'تم بنجاح' : 'Success')
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    protected function errorResponse($message, $statusCode = 400, $additionalData = []): JsonResponse
    {
        $currentLanguage = app()->getLocale();

        $errorMessage = is_array($message)
            ? ($message[$currentLanguage] ?? $message['en'] ?? 'Error occurred')
            : $message;

        $response = [
            'success' => false,
            'status_code' => $statusCode,
            'message' => $errorMessage
        ];

        if (!empty($additionalData)) {
            $response = array_merge($response, $additionalData);
        }

        return response()->json($response, $statusCode);
    }

    protected function validationErrorResponse(ValidationException $exception): JsonResponse
    {
        return $this->errorResponse(
            [
                'ar' => 'خطأ في البيانات المدخلة',
                'en' => 'Validation error'
            ],
            422,
            $exception->errors()
        );
    }

    protected function notFoundResponse($resource = 'Resource'): JsonResponse
    {
        return $this->errorResponse([
            'ar' => "$resource غير موجود",
            'en' => "$resource not found"
        ], 404);
    }

    protected function unauthorizedResponse(): JsonResponse
    {
        return $this->errorResponse([
            'ar' => 'غير مصرح لك بالوصول',
            'en' => 'Unauthorized access'
        ], 401);
    }

    protected function forbiddenResponse(): JsonResponse
    {
        return $this->errorResponse([
            'ar' => 'ليس لديك صلاحية للقيام بهذا الإجراء',
            'en' => 'You do not have permission to perform this action'
        ], 403);
    }

    protected function serverErrorResponse(string $message = 'An unexpected error occurred on the server.', int $status = 500)
{
    return response()->json([
        'success' => false,
        'message' => [
            'ar' => 'حدث خطأ غير متوقع في الخادم.',
            'en' => $message,
        ],
    ], $status);
}

    private function getDefaultErrorMessage($statusCode): array
    {
        $messages = [
            400 => ['ar' => 'طلب غير صحيح', 'en' => 'Bad request'],
            401 => ['ar' => 'غير مصرح', 'en' => 'Unauthorized'],
            403 => ['ar' => 'ممنوع', 'en' => 'Forbidden'],
            404 => ['ar' => 'غير موجود', 'en' => 'Not found'],
            422 => ['ar' => 'خطأ في البيانات', 'en' => 'Validation error'],
            500 => ['ar' => 'خطأ في الخادم', 'en' => 'Server error']
        ];

        return $messages[$statusCode] ?? ['ar' => 'خطأ غير معروف', 'en' => 'Unknown error'];
    }
}
