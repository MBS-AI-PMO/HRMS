<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\User;
use App\Models\company;
use App\Models\department;
use App\Models\designation;
use App\Models\Country;
use App\Models\office_shift;
use Spatie\Permission\Models\Role;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class UsersImport implements ToModel, WithHeadingRow, ShouldQueue, WithChunkReading, WithBatchInserts, WithValidation
{
    use Importable;

    public function model(array $row)
    {
        $companyName     = $row['company_name'] ?? null;
        $departmentName  = $row['department_name'] ?? null;
        $designationName = $row['designation_name'] ?? null;
        $shiftName       = $row['shift_name'] ?? null;
        $roleName        = $row['role_name'] ?? null;
        $countryName     = $row['country_name'] ?? null;

        $company = company::where('company_name', $companyName)->first();

        $department = null;
        if (!empty($departmentName) && $company) {
            $department = department::where('department_name', $departmentName)
                ->where('company_id', $company->id)
                ->first();
        }

        $designation = null;
        if (!empty($designationName) && $company && $department) {
            $designation = designation::where('designation_name', $designationName)
                ->where('company_id', $company->id)
                ->where('department_id', $department->id)
                ->first();
        }

        $officeShift = null;
        if (!empty($shiftName) && $company) {
            $officeShift = office_shift::where('shift_name', $shiftName)
                ->where('company_id', $company->id)
                ->first();
        }

        $role = Role::where('name', $roleName)->first();
        $country = null;
        if (!empty($countryName)) {
            $country = Country::where('name', $countryName)->first();
        }

        $user = User::create([
            'first_name'    => $row['first_name'],
            'last_name'     => $row['last_name'],
            'username'      => $row['username'],
            'email'         => $row['email'],
            'password'      => Hash::make($row['password']),
            'contact_no'    => $row['contact_no'] ?? null,
            'role_users_id' => $role?->id,
            'is_active'     => 1,
        ]);

        return new Employee([
            'id'              => $user->id,
            'first_name'      => $row['first_name'],
            'last_name'       => $row['last_name'],
            'staff_id'        => $row['staff_id'],
            'email'           => $row['email'],
            'contact_no'      => $row['contact_no'] ?? null,
            'joining_date'    => $row['joining_date'] ?? null,
            'date_of_birth'   => $row['date_of_birth'] ?? null,
            'gender'          => $row['gender'] ?? null,
            'address'         => $row['address'] ?? null,
            'city'            => $row['city'] ?? null,
            'country'         => $country?->id,
            'zip_code'        => $row['zip'] ?? null,
            'attendance_type' => $row['attendance_type'],
            'company_id'      => $company?->id,
            'department_id'   => $department?->id,
            'designation_id'  => $designation?->id,
            'office_shift_id' => $officeShift?->id,
            'role_users_id'   => $role?->id,
            'is_active'       => 1,
        ]);
    }

    public function rules(): array
    {
        return [
            '*.first_name'       => 'required|string',
            '*.last_name'        => 'required|string',
            '*.staff_id'         => 'required|numeric|unique:employees,staff_id',
            '*.email'            => 'required|email|unique:users,email',
            '*.contact_no'       => 'nullable|unique:users,contact_no',
            '*.joining_date'     => 'nullable|date_format:' . env('Date_Format'),
            '*.date_of_birth'    => 'nullable|date_format:' . env('Date_Format'),
            '*.gender'           => 'nullable|in:Male,Female,Other',
            '*.company_name'     => 'required|exists:companies,company_name',
            '*.department_name'  => 'nullable|exists:departments,department_name',
            '*.designation_name' => 'nullable|exists:designations,designation_name',
            '*.shift_name'       => 'nullable|exists:office_shifts,shift_name',
            '*.username'         => 'required|unique:users,username',
            '*.role_name'        => 'required|exists:roles,name',
            '*.password'         => 'required|min:4',
            '*.attendance_type'  => 'required|in:general,ip_based,location_based',
            '*.country_name'     => 'nullable|exists:countries,name',
        ];
    }

    public function customValidationMessages()
    {
        return [
            '*.staff_id.unique'         => 'Staff ID already exists.',
            '*.email.unique'            => 'Email already exists.',
            '*.username.unique'         => 'Username already exists.',
            '*.company_name.exists'     => 'Selected company does not exist.',
            '*.department_name.exists'  => 'Selected department does not exist.',
            '*.designation_name.exists' => 'Selected designation does not exist.',
            '*.shift_name.exists'       => 'Selected shift does not exist.',
            '*.role_name.exists'        => 'Selected role does not exist.',
            '*.country_name.exists'     => 'Selected country does not exist.',
            '*.joining_date.date_format'  => 'Joining date format is invalid.',
            '*.date_of_birth.date_format' => 'Date of birth format is invalid.',
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function batchSize(): int
    {
        return 1000;
    }
}