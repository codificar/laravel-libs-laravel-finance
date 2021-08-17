<?php

namespace Codificar\Finance\Imports;

use Carbon\Carbon;
use Codificar\Finance\Models\LibModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Ledger, Finance;
use Session;

class PaymentsImport implements ToCollection, WithStartRow, WithCustomCsvSettings
{
    protected $delimiter;
    protected $dateFormat;

    public function __construct($delimiter, $dateFormat) {
        $this->delimiter = $delimiter;
        $this->dateFormat = $dateFormat;
    }

    /**
     * @return array
     */
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => $this->delimiter
        ];
    }

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows)
    {
        try {
            $bulkInsertion = [];

            foreach ($rows as $row) {
                $ledger = Ledger::whereProviderId(trim($row[0]))->first();
                $compensationDate = Carbon::createFromFormat($this->dateFormat, trim($row[2]))->format('Y-m-d H:i:s');
                
                if ($ledger) {
                    $finance = [
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'compensation_date' => $compensationDate,
                        'ledger_id' => $ledger->id,
                        'value' => trim($row[1]) * -1,
                        'reason' => LibModel::DEPOSIT_IN_ACCOUNT,
                        'description' => trim($row[3]),
                    ];

                    array_push($bulkInsertion, $finance);
                }

            }

            foreach (collect($bulkInsertion)->chunk(250) as $item) {
                Finance::insert($item->toArray());
            }

            Session::flash('success', trans('financeTrans::finance.success_import'));
            return true;
        } catch (\Throwable $th) {
            Session::flash('danger', trans('financeTrans::finance.success_error'));
            \Log::error($th->getMessage());
        }
    }
}