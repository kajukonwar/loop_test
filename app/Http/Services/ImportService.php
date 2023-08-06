<?php 
namespace App\Http\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class ImportService
{
    private $type = null;

    public function importToDb($type)
    {
        if (!in_array($type, ['customers', 'products'])) {
            $msg = 'Invalid type';
            $this->logAndThrowException($msg);
        }
        $this->type = $type;

        if (empty(config('loop.data_source.url.'.$type))) {
            $msg = 'The URL to download the '.$type.' data is missing';
            $this->logAndThrowException($msg);
        }

        if (empty(config('loop.data_source.auth.username'))) {
            $msg = 'The username to access the URL is missing';
            $this->logAndThrowException($msg);
        }

        if (empty(config('loop.data_source.auth.password'))) {
            $msg = 'The password to access the URL is missing';
            $this->logAndThrowException($msg);
        }

        $url = config('loop.data_source.url.'.$type);
        $response = Http::withBasicAuth(config('loop.data_source.auth.username'), config('loop.data_source.auth.password'))->get($url);
        if (!$response->successful()) {
            $msg = $type.' data download request failed';
            $this->logAndThrowException($msg);
        }

        $csv = $response->body();
        Storage::put(config('loop.storage.path.'.$type), $csv);
        $tmp_path = storage_path('app/'.config('loop.storage.path.'.$type));
        Log::info($type.' data downloaded successfully and saved in '.$tmp_path);
        
        $imported_count = $this->processImport($tmp_path);
        unlink($tmp_path);
        return $imported_count;
    }

    public function processImport($tmp_path)
    {
        $record_count = 0;
        $iteration = 0;
        try {
            LazyCollection::make(function () use($tmp_path) {
                $handle = fopen($tmp_path, 'r');
                while (($line = fgetcsv($handle)) !== false) {
                  yield $line;
                }
                fclose($handle);
              })
              ->skip(1)
              ->chunk(1000)
              ->each(function (LazyCollection $chunk) use (&$record_count, &$iteration) {
                $iteration++;
                $records = $chunk->map(function ($row) {
                   if ($this->type == 'customers') {
                       return $this->prepareCustomerData($row);
                   }

                   if ($this->type == 'products') {
                       return $this->prepareProductData($row);
                   }
                })->toArray();
                
                DB::table($this->type)->insert($records);
                $record_count = $record_count + count($records);
                Log::info($this->type.' data inserted successfuly', ['records_imported' => count($records), 'iteration' => $iteration]);
              });

              Log::info('Import Success', ['total_imported' => $record_count]);
              return $record_count;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw $e;
        }
    }

    private function logAndThrowException($msg)
    {
        Log::error($msg);
        throw new Exception($msg);
    }

    private function prepareCustomerData($row)
    {
        $date_added = Carbon::parse($row[4]);
        $date_added = $date_added->format('Y-m-d H:i:s');
        return [
            "id" => $row[0],
            "job_title" => $row[1],
            "email" => $row[2],
            "name" => $row[3],
            "phone" => $row[5],
            "date_added" => $date_added
        ];
    }

    private function prepareProductData($row)
    {
        return [
            "id" => $row[0],
            "product" => $row[1],
            "price" => $row[2]
        ];
    }
}