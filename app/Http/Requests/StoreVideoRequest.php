<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVideoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'videos' => ['required', 'array', 'min:1', 'max:10'],
            'videos.*' => [
                'file',
                'mimes:mp4,mov,avi,webm',
                'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm',
                'max:102400', // 100 MB
            ],
        ];
    }
}
