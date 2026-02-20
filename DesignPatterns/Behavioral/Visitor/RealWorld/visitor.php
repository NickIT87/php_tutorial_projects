<?php

namespace DesignPatterns\Behavioral\Visitor\RealWorld;

use NumberFormatter;

/**
 * The Component interface declares a method of accepting visitor objects.
 *
 * In this method, a Concrete Component must call a specific Visitor's method
 * that has the same parameter type as that component.
 */
interface Entity
{
    public function accept(Visitor $visitor): string;
}

/**
 * The Company Concrete Component.
 */
class Company implements Entity
{
    private $name;

    /**
     * @var Department[]
     */
    private $departments;

    public function __construct(string $name, array $departments)
    {
        $this->name = $name;
        $this->departments = $departments;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDepartments(): array
    {
        return $this->departments;
    }

    // ...

    public function accept(Visitor $visitor): string
    {
        // See, the Company component must call the visitCompany method. The
        // same principle applies to all components.
        return $visitor->visitCompany($this);
    }
}

/**
 * The Department Concrete Component.
 */
class Department implements Entity
{
    private $name;

    /**
     * @var Employee[]
     */
    private $employees;

    public function __construct(string $name, array $employees)
    {
        $this->name = $name;
        $this->employees = $employees;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmployees(): array
    {
        return $this->employees;
    }

    public function getCost(): int
    {
        $cost = 0;
        foreach ($this->employees as $employee) {
            $cost += $employee->getSalary();
        }

        return $cost;
    }

    // ...

    public function accept(Visitor $visitor): string
    {
        return $visitor->visitDepartment($this);
    }
}

/**
 * The Employee Concrete Component.
 */
class Employee implements Entity
{
    private $name;

    private $position;

    private $salary;

    public function __construct(string $name, string $position, int $salary)
    {
        $this->name = $name;
        $this->position = $position;
        $this->salary = $salary;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function getSalary(): int
    {
        return $this->salary;
    }

    // ...

    public function accept(Visitor $visitor): string
    {
        return $visitor->visitEmployee($this);
    }
}

/**
 * The Visitor interface declares a set of visiting methods for each of the
 * Concrete Component classes.
 */
interface Visitor
{
    public function visitCompany(Company $company): string;

    public function visitDepartment(Department $department): string;

    public function visitEmployee(Employee $employee): string;
}

/**
 * The Concrete Visitor must provide implementations for every single class of
 * the Concrete Components.
 */
// class SalaryReport implements Visitor
// {
//     public function visitCompany(Company $company): string
//     {
//         $output = "";
//         $total = 0;

//         foreach ($company->getDepartments() as $department) {
//             $total += $department->getCost();
//             $output .= "\n--" . $this->visitDepartment($department);
//         }

//         $output = $company->getName() .
//             " (" . money_format("%i", $total) . ")\n" . $output;

//         return $output;
//     }

//     public function visitDepartment(Department $department): string
//     {
//         $output = "";

//         foreach ($department->getEmployees() as $employee) {
//             $output .= "   " . $this->visitEmployee($employee);
//         }

//         $output = $department->getName() .
//             " (" . money_format("%i", $department->getCost()) . ")\n\n" .
//             $output;

//         return $output;
//     }

//     public function visitEmployee(Employee $employee): string
//     {
//         return money_format("%#6n", $employee->getSalary()) .
//             " " . $employee->getName() .
//             " (" . $employee->getPosition() . ")\n";
//     }
// }

class SalaryReport implements Visitor
{
    private NumberFormatter $fmt;

    public function __construct()
    {
        $this->fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
    }

    private function money(int $amount): string
    {
        return $this->fmt->formatCurrency($amount, 'USD');
    }

    public function visitCompany(Company $company): string
    {
        $output = "";
        $total = 0;

        foreach ($company->getDepartments() as $department) {
            $total += $department->getCost();
            $output .= "\n--" . $this->visitDepartment($department);
        }

        return $company->getName() .
            " (" . $this->money($total) . ")\n" .
            $output;
    }

    public function visitDepartment(Department $department): string
    {
        $output = "";

        foreach ($department->getEmployees() as $employee) {
            $output .= "   " . $this->visitEmployee($employee);
        }

        return $department->getName() .
            " (" . $this->money($department->getCost()) . ")\n\n" .
            $output;
    }

    public function visitEmployee(Employee $employee): string
    {
        return $this->money($employee->getSalary()) .
            " " . $employee->getName() .
            " (" . $employee->getPosition() . ")\n";
    }
}

// for json export
class JSONExport implements Visitor
{
    public function visitCompany(Company $company): string
    {
        $data = [
            'type' => 'company',
            'name' => $company->getName(),
            'departments' => [],
        ];

        foreach ($company->getDepartments() as $department) {
            $data['departments'][] = json_decode(
                $department->accept($this),
                true
            );
        }

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    public function visitDepartment(Department $department): string
    {
        $data = [
            'type' => 'department',
            'name' => $department->getName(),
            'cost' => $department->getCost(),
            'employees' => [],
        ];

        foreach ($department->getEmployees() as $employee) {
            $data['employees'][] = json_decode(
                $employee->accept($this),
                true
            );
        }

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    public function visitEmployee(Employee $employee): string
    {
        return json_encode([
            'type' => 'employee',
            'name' => $employee->getName(),
            'position' => $employee->getPosition(),
            'salary' => $employee->getSalary(),
        ], JSON_PRETTY_PRINT);
    }
}


/**
 * The client code.
 */

$mobileDev = new Department("Mobile Development", [
    new Employee("Albert Falmore", "designer", 100000),
    new Employee("Ali Halabay", "programmer", 100000),
    new Employee("Sarah Konor", "programmer", 90000),
    new Employee("Monica Ronaldino", "QA engineer", 31000),
    new Employee("James Smith", "QA engineer", 30000),
]);
$techSupport = new Department("Tech Support", [
    new Employee("Larry Ulbrecht", "supervisor", 70000),
    new Employee("Elton Pale", "operator", 30000),
    new Employee("Rajeet Kumar", "operator", 30000),
    new Employee("John Burnovsky", "operator", 34000),
    new Employee("Sergey Korolev", "operator", 35000),
]);
$company = new Company("SuperStarDevelopment", [$mobileDev, $techSupport]);

setlocale(LC_MONETARY, 'en_US');
$report = new SalaryReport();

echo "Client: I can print a report for a whole company:\n\n";
echo $company->accept($report);

echo "\nClient: ...or for different entities " .
    "such as an employee, a department, or the whole company:\n\n";
$someEmployee = new Employee("Some employee", "operator", 35000);
$differentEntities = [$someEmployee, $techSupport, $company];
foreach ($differentEntities as $entity) {
    echo $entity->accept($report) . "\r\n";
}

// json export with same class name
$export = new JSONExport(); 
echo $company->accept($export);