<?php

use App\Models\Company;
use App\Models\LeadReport;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function leadReportCompany(string $code): Company
{
    return Company::firstOrCreate(
        ['code' => $code],
        ['name' => 'Lead Report Company ' . $code]
    );
}

test('user can access lead reports from another company', function () {
    Storage::fake('local');

    $originCompany = leadReportCompany('LEAD-ORIGIN');
    $viewerCompany = leadReportCompany('LEAD-VIEWER');
    $uploader = User::factory()->create(['company_id' => $originCompany->id]);
    $viewer = User::factory()->create(['company_id' => $viewerCompany->id]);

    Storage::disk('local')->put('lead_reports/origin.md', '# Lead Report Origin');

    $report = LeadReport::create([
        'title' => 'Lead Report Lintas Company',
        'original_filename' => 'origin.md',
        'file_path' => 'lead_reports/origin.md',
        'content' => '# Lead Report Origin',
        'uploaded_by' => $uploader->id,
        'company_id' => $originCompany->id,
        'report_date' => '2026-05-04',
    ]);

    $this->actingAs($viewer)
        ->get(route('lead-reports.index'))
        ->assertOk()
        ->assertSee('Lead Report Lintas Company');

    $this->actingAs($viewer)
        ->get(route('lead-reports.show', $report))
        ->assertOk()
        ->assertSee('Lead Report Origin');

    $this->actingAs($viewer)
        ->get(route('lead-reports.download', $report))
        ->assertOk();
});
