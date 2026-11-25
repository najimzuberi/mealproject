<?php

namespace App\Modules\Reports\Services;

use App\Modules\Reports\Models\Report;

class ReportExportService
{
    public function toCsv(Report $report): string
    {
        $lines = [];
        $lines[] = ['Reference', 'Title', 'Type', 'Status', 'Period', 'Project'];
        $lines[] = [
            $report->reference_number,
            $report->title,
            $report->report_type instanceof \BackedEnum ? $report->report_type->value : $report->report_type,
            $report->status instanceof \BackedEnum ? $report->status->value : $report->status,
            $report->reportingPeriod?->name,
            $report->project?->name,
        ];
        $lines[] = [];
        $lines[] = [
            'Indicator Code',
            'Indicator Name',
            'Baseline',
            'Target',
            'Actual',
            'Achievement %',
            'Variance',
            'Status',
            'Reporting Period ID',
            'Project ID',
        ];

        foreach ($report->indicatorResults as $row) {
            $lines[] = [
                $row->indicator_code,
                $row->indicator_name,
                $row->baseline,
                $row->target,
                $row->actual,
                $row->achievement_percent,
                $row->variance,
                $row->performance_status,
                $row->reporting_period_id,
                $row->project_id,
            ];
        }

        $csv = '';
        foreach ($lines as $line) {
            $csv .= $this->csvLine(is_array($line) ? $line : [$line])."\n";
        }

        return $csv;
    }

    /**
     * Minimal text PDF without external packages.
     */
    public function toPdf(Report $report): string
    {
        $status = $report->status instanceof \BackedEnum ? $report->status->value : (string) $report->status;
        $type = $report->report_type instanceof \BackedEnum ? $report->report_type->value : (string) $report->report_type;

        $lines = [
            'MEAL MASTER Performance Report',
            'Reference: '.$report->reference_number,
            'Title: '.$report->title,
            'Type: '.$type,
            'Status: '.$status,
            'Period: '.($report->reportingPeriod?->name ?? 'N/A'),
            'Project: '.($report->project?->name ?? 'N/A'),
            '',
            'Executive Summary',
            $this->wrap($report->executive_summary ?? ''),
            '',
            'Performance Summary',
            $this->wrap($report->performance_summary ?? ''),
            '',
            'Challenges',
            $this->wrap($report->challenges ?? ''),
            '',
            'Recommendations',
            $this->wrap($report->recommendations ?? ''),
            '',
            'Indicator Results',
        ];

        foreach ($report->indicatorResults as $row) {
            $lines[] = sprintf(
                '%s | %s | baseline=%s target=%s actual=%s ach=%s%% (%s)',
                $row->indicator_code,
                $row->indicator_name,
                $row->baseline,
                $row->target,
                $row->actual,
                $row->achievement_percent,
                $row->performance_status,
            );
        }

        if ($report->ai_assisted) {
            $lines[] = '';
            $lines[] = 'Note: Contains AI-assisted draft content pending human approval.';
        }

        $lines[] = '';
        $lines[] = 'Prepared by user #'.($report->prepared_by ?? 'n/a');
        $lines[] = 'Approved at: '.($report->approved_at?->toDateTimeString() ?? 'n/a');

        return $this->buildSimplePdf($lines);
    }

    /**
     * @param  list<string|null>  $fields
     */
    protected function csvLine(array $fields): string
    {
        return implode(',', array_map(function ($field) {
            $value = (string) ($field ?? '');
            $value = str_replace('"', '""', $value);

            return '"'.$value.'"';
        }, $fields));
    }

    protected function wrap(?string $text): string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '(none)';
        }

        return wordwrap($text, 90, "\n", true);
    }

    /**
     * @param  list<string>  $lines
     */
    protected function buildSimplePdf(array $lines): string
    {
        $content = "BT /F1 11 Tf 40 800 Td 14 TL\n";
        foreach ($lines as $index => $line) {
            $safe = $this->pdfEscape($line);
            if ($index === 0) {
                $content .= "({$safe}) Tj\n";
            } else {
                $content .= "T* ({$safe}) Tj\n";
            }
        }
        $content .= "ET\n";

        $objects = [];
        $objects[] = '1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj';
        $objects[] = '2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj';
        $objects[] = '3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>endobj';
        $objects[] = '4 0 obj<< /Length '.strlen($content)." >>stream\n{$content}endstream endobj";
        $objects[] = '5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object."\n";
        }

        $xrefPos = strlen($pdf);
        $pdf .= 'xref\n0 '.(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= 'trailer<< /Size '.(count($objects) + 1).' /Root 1 0 R >>\n';
        $pdf .= "startxref\n{$xrefPos}\n%%EOF";

        return $pdf;
    }

    protected function pdfEscape(string $text): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $text = preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;

        return $text;
    }
}
