<?php

use App\Models\Company;
use App\Models\Employee;
use App\Support\Validation\SharedLaravelValidationRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('builds company validation rules from the shared schema', function () {
    $existingCompany = Company::factory()->create([
        'name' => 'Existing Company',
        'email' => 'existing@example.com',
    ]);

    $rules = SharedLaravelValidationRules::for('company');

    $validator = Validator::make([
        'id' => null,
        'name' => $existingCompany->name,
        'email' => $existingCompany->email,
        'phone' => null,
        'address' => null,
        'is_active' => true,
    ], $rules);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain('name', 'email');
});

it('ignores the current company when unique rules are built from the shared schema', function () {
    $company = Company::factory()->create([
        'name' => 'Existing Company',
        'email' => 'existing@example.com',
    ]);

    $rules = SharedLaravelValidationRules::for('company', [
        'company' => $company,
    ]);

    $validator = Validator::make([
        'id' => $company->id,
        'name' => $company->name,
        'email' => $company->email,
        'phone' => '123',
        'address' => 'Updated address',
        'is_active' => false,
    ], $rules);

    expect($validator->fails())->toBeFalse();
});

it('builds employee validation rules from the shared schema', function () {
    $company = Company::factory()->create();

    $rules = SharedLaravelValidationRules::for('employee');

    $validator = Validator::make([
        'id' => null,
        'company_id' => $company->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'active' => true,
    ], $rules);

    expect($validator->fails())->toBeFalse();
});
