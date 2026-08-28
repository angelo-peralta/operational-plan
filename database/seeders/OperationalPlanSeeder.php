<?php

namespace Database\Seeders;

use App\Actions\OperationalPlans\RecordOperationalPlanStatus;
use App\Enums\OperationalPlanStatus;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\OperationalPlan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperationalPlanSeeder extends Seeder
{
    use WithoutModelEvents;

    public function __construct(private RecordOperationalPlanStatus $recordStatus) {}

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        DB::transaction(function (): void {
            $academicYear = AcademicYear::query()
                ->where('start_year', 2025)
                ->where('end_year', 2026)
                ->firstOrFail();
            $department = Department::query()->where('code', 'CPRINT')->firstOrFail();

            if (OperationalPlan::query()
                ->whereBelongsTo($academicYear)
                ->whereBelongsTo($department)
                ->exists()) {
                return;
            }

            $creator = User::query()->where('email', 'department@example.com')->firstOrFail();
            $ict = Department::query()->where('code', 'ICT')->firstOrFail();
            $qualityAssurance = Department::query()->where('code', 'QA')->firstOrFail();

            $operationalPlan = OperationalPlan::query()->create([
                'academic_year_id' => $academicYear->id,
                'department_id' => $department->id,
                'accountable_user_id' => null,
                'accountable_name' => null,
                'accountable_position' => 'CPRINT Director',
                'goal' => 'To ensure the full integration of ethical AI and digital technologies in teaching, learning, research, and administrative processes through 100% adoption, capacity building, and efficient, timely, and high-quality reporting systems.',
                'status' => OperationalPlanStatus::Draft,
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
            ]);

            $keyResultArea = $operationalPlan->keyResultAreas()->create([
                'code' => 'KRA 1',
                'name' => 'Quality Teaching, Learning, and Student Services',
                'description' => null,
                'sort_order' => 1,
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
            ]);

            foreach ($this->planItems() as $index => $itemData) {
                $linkIctAndQa = $itemData['link_ict_and_qa'] ?? false;
                unset($itemData['link_ict_and_qa']);

                $planItem = $keyResultArea->planItems()->create([
                    ...$itemData,
                    'sort_order' => $index + 1,
                    'created_by' => $creator->id,
                    'updated_by' => $creator->id,
                ]);

                if ($linkIctAndQa) {
                    $planItem->coAccountableDepartments()->sync([
                        $ict->id,
                        $qualityAssurance->id,
                    ]);
                }
            }

            $this->recordStatus->handle(
                $operationalPlan,
                $creator,
                null,
                OperationalPlanStatus::Draft,
            );
        });
    }

    /** @return list<array<string, mixed>> */
    private function planItems(): array
    {
        $ethicalAiObjective = 'Ethical usage and integration of AI tools in teaching and learning modules';
        $professionalDevelopmentObjective = '100% professional development and internalization of AI-based LMS for Student Services';

        return [
            [
                'objective' => $ethicalAiObjective,
                'strategy' => 'Develop AI Usage Guidelines',
                'kpi_target_text' => 'Institutional research-based AI guidelines are developed and approved',
                'resources_needed' => 'AI Policy Development and Research Team',
                'documentary_evidence_requirements' => ['Approved AI Guidelines and Policy Documents'],
                'manual_co_accountable_units' => [],
            ],
            [
                'objective' => $ethicalAiObjective,
                'strategy' => 'Monitor and Evaluate AI Use',
                'kpi_target_text' => 'At least 1 monitoring/evaluation study conducted per year on AI use in instruction',
                'resources_needed' => 'Digital Tools and Platforms for AI Monitoring and Evaluation',
                'documentary_evidence_requirements' => ['Monitoring and Evaluation Reports on AI Use'],
                'manual_co_accountable_units' => ['VPA', 'CEARA'],
                'link_ict_and_qa' => true,
            ],
            [
                'objective' => $ethicalAiObjective,
                'strategy' => 'Monitor and Evaluate AI Use',
                'kpi_target_text' => '80% of programs evaluated using AI integration research-based tools',
                'resources_needed' => 'Digital Tools and Platforms for AI Monitoring and Evaluation',
                'documentary_evidence_requirements' => ['Program Evaluation Records and Tools'],
                'manual_co_accountable_units' => [],
            ],
            [
                'objective' => $professionalDevelopmentObjective,
                'strategy' => 'Build faculty capability on AI-assisted research',
                'kpi_target_text' => '100% of researchers trained on AI tools for research',
                'resources_needed' => 'Training and Capability-Building Resources',
                'documentary_evidence_requirements' => ['Training Records', 'Certificates'],
                'manual_co_accountable_units' => [],
            ],
            [
                'objective' => $professionalDevelopmentObjective,
                'strategy' => 'Build faculty capability on AI-assisted research',
                'kpi_target_text' => 'At least 2 AI-in-research capability workshops are conducted per year',
                'resources_needed' => 'Qualified Trainers and Resource Persons',
                'documentary_evidence_requirements' => ['Research Outputs Demonstrating AI Utilization'],
                'manual_co_accountable_units' => [],
            ],
            [
                'objective' => $professionalDevelopmentObjective,
                'strategy' => 'Monitor and promote AI utilization in research outputs',
                'kpi_target_text' => 'At least 70–80% of research outputs demonstrate use of AI tools',
                'resources_needed' => 'AI Tools and Software Subscriptions for Research',
                'documentary_evidence_requirements' => ['Research Outputs Demonstrating AI Utilization'],
                'manual_co_accountable_units' => [],
            ],
            [
                'objective' => $professionalDevelopmentObjective,
                'strategy' => 'Monitor and promote AI utilization in research outputs',
                'kpi_target_text' => 'At least 1 research study on AI applications in research processes completed per year',
                'resources_needed' => 'Training and Implementation Support Materials',
                'documentary_evidence_requirements' => ['Completed Research Study on AI in Research Processes'],
                'manual_co_accountable_units' => [],
            ],
            [
                'objective' => $professionalDevelopmentObjective,
                'strategy' => 'Develop Research Monitoring System',
                'kpi_target_text' => '100% of research projects tracked through a centralized monitoring system',
                'resources_needed' => 'Centralized Research Monitoring System / Dashboard',
                'documentary_evidence_requirements' => ['System Screenshots and Dashboard Reports'],
                'manual_co_accountable_units' => [],
            ],
            [
                'objective' => $professionalDevelopmentObjective,
                'strategy' => 'Develop Research Monitoring System',
                'kpi_target_text' => 'Real-time monitoring dashboard established and operational',
                'resources_needed' => 'Research Monitoring Personnel / System Administrator',
                'documentary_evidence_requirements' => ['Research Tracking Database / Monitoring Logs'],
                'manual_co_accountable_units' => [],
            ],
            [
                'objective' => $professionalDevelopmentObjective,
                'strategy' => 'Strengthen compliance through monitoring and feedback mechanisms',
                'kpi_target_text' => '100% on-time submission of required reports',
                'resources_needed' => 'Standardized Reporting and Monitoring Tools',
                'documentary_evidence_requirements' => ['Submission Records and Reports'],
                'manual_co_accountable_units' => [],
            ],
            [
                'objective' => $professionalDevelopmentObjective,
                'strategy' => 'Strengthen compliance through monitoring and feedback mechanisms',
                'kpi_target_text' => '0% delayed submissions across all research projects',
                'resources_needed' => 'Standardized Reporting and Monitoring Tools',
                'documentary_evidence_requirements' => ['Compliance Reports'],
                'manual_co_accountable_units' => [],
            ],
        ];
    }
}
