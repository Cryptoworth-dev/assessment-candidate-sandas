<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\ExpenseCategory;

class StoreExpenseRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description'  => ['required', 'string', 'max:255'],
            'amount'       => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'category'     => ['required', Rule::enum(ExpenseCategory::class)],
            'expense_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'amount.gt'  => 'The amount must be greater than zero.',
            'amount.max' => 'The amount exceeds the maximum supported value.',
            'expense_date.before_or_equal' => 'The expense date cannot be in the future.',
        ];
    }
}
