<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv as CsvWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function index()
    {
        return view('exports.index');
    }

    public function excel(Request $request): StreamedResponse
    {
        $transactions = $this->getFilteredTransactions($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Transaksi');

        $sheet->setCellValue('A1', 'Tanggal');
        $sheet->setCellValue('B1', 'Deskripsi');
        $sheet->setCellValue('C1', 'Kategori');
        $sheet->setCellValue('D1', 'Jenis');
        $sheet->setCellValue('E1', 'Nominal');

        $row = 2;
        $totalIncome = 0;
        $totalExpense = 0;
        $categoryTotals = [];

        foreach ($transactions as $transaction) {
            $jenis = $transaction->type === 'income' ? 'Pemasukan' : 'Pengeluaran';
            $nominal = (float) $transaction->amount;

            $sheet->setCellValue('A' . $row, $transaction->transaction_date->format('Y-m-d'));
            $sheet->setCellValue('B' . $row, $transaction->note ?? '-');
            $sheet->setCellValue('C' . $row, $transaction->category?->name ?? '-');
            $sheet->setCellValue('D' . $row, $jenis);
            $sheet->setCellValue('E' . $row, $nominal);

            if ($transaction->type === 'income') {
                $totalIncome += $nominal;
            } else {
                $totalExpense += $nominal;
            }

            $catName = $transaction->category?->name ?? '-';
            if (!isset($categoryTotals[$catName])) {
                $categoryTotals[$catName] = ['income' => 0, 'expense' => 0, 'name' => $catName];
            }
            $categoryTotals[$catName][$transaction->type] += $nominal;

            $row++;
        }

        $summarySheet = $spreadsheet->createSheet();
        $summarySheet->setTitle('Ringkasan');

        $summarySheet->setCellValue('A1', 'Kategori');
        $summarySheet->setCellValue('B1', 'Pemasukan');
        $summarySheet->setCellValue('C1', 'Pengeluaran');

        $sRow = 2;
        foreach ($categoryTotals as $total) {
            $summarySheet->setCellValue('A' . $sRow, $total['name']);
            $summarySheet->setCellValue('B' . $sRow, $total['income']);
            $summarySheet->setCellValue('C' . $sRow, $total['expense']);
            $sRow++;
        }

        $summarySheet->setCellValue('A' . $sRow, 'Total');
        $summarySheet->setCellValue('B' . $sRow, $totalIncome);
        $summarySheet->setCellValue('C' . $sRow, $totalExpense);

        foreach ([$sheet, $summarySheet] as $s) {
            foreach (range('A', 'E') as $col) {
                $s->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="transaksi.xlsx"');

        return $response;
    }

    public function csv(Request $request): StreamedResponse
    {
        $transactions = $this->getFilteredTransactions($request);

        $response = new StreamedResponse(function () use ($transactions) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Tanggal', 'Deskripsi', 'Kategori', 'Jenis', 'Nominal']);

            foreach ($transactions as $transaction) {
                $jenis = $transaction->type === 'income' ? 'Pemasukan' : 'Pengeluaran';
                fputcsv($handle, [
                    $transaction->transaction_date->format('Y-m-d'),
                    $transaction->note ?? '-',
                    $transaction->category?->name ?? '-',
                    $jenis,
                    (float) $transaction->amount,
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="transaksi.csv"');

        return $response;
    }

    private function getFilteredTransactions(Request $request)
    {
        $query = Transaction::forUser($request->user()->id)
            ->with('category')
            ->latestFirst();

        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('category_id')) {
            $query->byCategory($request->category_id);
        }

        return $query->get();
    }
}
