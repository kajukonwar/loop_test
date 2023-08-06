<?php 
namespace App\Http\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class CustomerService
{

    public function importToDb()
    {
        if (empty(config('loop.data_source.url.customers'))) {
            $msg = 'The URL to download the customers data is missing';
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

        $url = config('loop.data_source.url.customers');
        $response = Http::withBasicAuth(config('loop.data_source.auth.username'), config('loop.data_source.auth.password'))->get($url);
        if (!$response->successful()) {
            $msg = 'Customer data download request failed';
            $this->logAndThrowException($msg);
        }

        $csv = $response->body();
        Storage::put(config('loop.storage.path.customers'), $csv);
        $tmp_path = storage_path('app/'.config('loop.storage.path.customers'));
        Log::info('Customer data downloaded successfully and saved in '.$tmp_path);
        
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
                })->toArray();
                
                DB::table('customers')->insert($records);
                $record_count = $record_count + count($records);
                Log::info('Customer data inserted successfuly', ['records_imported' => count($records), 'iteration' => $iteration]);
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
}