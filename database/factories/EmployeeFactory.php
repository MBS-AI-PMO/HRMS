<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Model;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define(Employee::class, function (Faker $faker) {
    return [

		'first_name'=> $faker->name,
		'last_name'=> $faker->name,
		'email' => $faker->unique()->safeEmail,
		'contact_no' => $faker->phoneNumber,
		'date_of_birth' => $faker->dateTimeBetween('1990-01-01', '2002-12-31')
			->format('d/m/Y'),
		'gender' => $faker->randomElement(['Male', 'Female']),
		'company_id' => Company::all()->random()->id,
		'department_id' => Department::all()->random()->id,
		'designation_id' => Designation::all()->random()->id

    ];
});
