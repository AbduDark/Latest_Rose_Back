<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use App\Traits\ApiResponseTrait;

class CommentRequest extends FormRequest
{
    use ApiResponseTrait;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'lesson_id' => [
                'required',
                'integer',
                'exists:lessons,id'
            ],
            'content' => [
                'required',
                'string',
                'min:1',
                'max:1000',
                'regex:/^(?!\s*$).+/' // لا يمكن أن يكون فارغ أو مسافات فقط
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'lesson_id.required' => 'معرف الدرس مطلوب|Lesson ID is required',
            'lesson_id.integer' => 'معرف الدرس يجب أن يكون رقم صحيح|Lesson ID must be an integer',
            'lesson_id.exists' => 'الدرس المحدد غير موجود|The selected lesson does not exist',

            'content.required' => 'محتوى التعليق مطلوب|Comment content is required',
            'content.string' => 'محتوى التعليق يجب أن يكون نص|Comment content must be a string',
            'content.min' => 'التعليق يجب أن يحتوي على حرف واحد على الأقل|Comment must be at least 1 character',
            'content.max' => 'التعليق لا يجب أن يتجاوز 1000 حرف|Comment must not exceed 1000 characters',
            'content.regex' => 'التعليق لا يمكن أن يكون فارغاً أو مسافات فقط|Comment cannot be empty or only spaces'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'lesson_id' => 'معرف الدرس|Lesson ID',
            'content' => 'محتوى التعليق|Comment content'
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors()->toArray();

        // تنسيق الأخطاء للعرض بشكل أفضل
        $formattedErrors = [];
        foreach ($errors as $field => $messages) {
            $formattedErrors[$field] = $messages[0]; // أخذ أول رسالة خطأ فقط
        }

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'status_code' => 422,
                'message' => [
                    'ar' => 'خطأ في البيانات المدخلة',
                    'en' => 'Validation error'
                ],
                'errors' => $formattedErrors
            ], 422)
        );
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // تنظيف محتوى التعليق من المسافات الزائدة
        if ($this->has('content')) {
            $this->merge([
                'content' => trim($this->input('content'))
            ]);
        }

        // التأكد من أن lesson_id رقم صحيح
        if ($this->has('lesson_id')) {
            $this->merge([
                'lesson_id' => (int) $this->input('lesson_id')
            ]);
        }
    }
}
