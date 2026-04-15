<?php

namespace App\Http\Requests;

use App\Enums\PointCategoryEnum;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePointRuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['sometimes', new Enum(PointCategoryEnum::class)],
            'event_name' => 'required|string|max:255|unique:point_rules,event_name,' . $this->route('point_rule')->id,
            'points' => 'required|integer|between:-1000,1000',
            'operator' => 'sometimes|in:<,<=,>,>=,==,BETWEEN',
            'min_value' => 'sometimes|integer',
            'max_value' => 'required_if:operator,BETWEEN|nullable|integer|gt:min_value',
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
