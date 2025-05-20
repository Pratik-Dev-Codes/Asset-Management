<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Using policies for authorization
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $reportId = $this->route('report') ? $this->route('report')->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('reports', 'name')->ignore($reportId),
            ],
            'description' => 'nullable|string|max:1000',
            'type' => [
                'required',
                'string',
                Rule::in([
                    'asset', 'inventory', 'maintenance', 'depreciation', 'custom',
                ]),
            ],
            'filters' => 'nullable|array',
            'filters.*.field' => 'required_with:filters|string',
            'filters.*.operator' => 'required_with:filters.*.field|string',
            'filters.*.value' => 'nullable',
            'columns' => 'required|array|min:1',
            'columns.*' => 'string|distinct',
            'sorting' => 'nullable|array',
            'sorting.field' => 'required_with:sorting|string',
            'sorting.direction' => 'required_with:sorting|in:asc,desc',
            'grouping' => 'nullable|array',
            'grouping.*.field' => 'required_with:grouping|string',
            'is_public' => 'boolean',
            'schedule' => 'nullable|array',
            'schedule.frequency' => 'required_with:schedule|in:daily,weekly,monthly,quarterly',
            'schedule.time' => 'required_with:schedule|date_format:H:i',
            'schedule.recipients' => 'required_with:schedule|array',
            'schedule.recipients.*' => 'email',
            'chart_options' => 'nullable|array',
            'chart_options.type' => 'required_with:chart_options|in:bar,line,pie,doughnut,radar,polarArea',
            'chart_options.title' => 'nullable|string|max:255',
            'chart_options.x_axis' => 'required_with:chart_options|string',
            'chart_options.y_axis' => 'required_with:chart_options|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'The report name is required.',
            'name.unique' => 'A report with this name already exists.',
            'type.required' => 'The report type is required.',
            'type.in' => 'The selected report type is invalid.',
            'columns.required' => 'At least one column must be selected.',
            'columns.array' => 'Columns must be an array.',
            'columns.min' => 'At least one column must be selected.',
            'filters.array' => 'Filters must be an array.',
            'sorting.array' => 'Sorting configuration must be an array.',
            'grouping.array' => 'Grouping configuration must be an array.',
        ];
    }
}
