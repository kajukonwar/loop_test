<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Services\ImportService;

class ImportData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports sample customer or products data';

    /**
     * Execute the console command.
     */
    public function handle(ImportService $import)
    {
        $type = $this->ask('Please enter 1 to import customers data or enter 2 to import products data');
        if (!in_array($type, [1,2])) {
            $this->error('Please enter either 1 or 2. 1- to import customers data, 2- to import products data');
            return false;
        }
        $type = ($type == 1) ? 'customers' : 'products';
        try {
            $imported_records = $import->importToDb($type);
            $this->info('Total '.$imported_records.' records were imported successfuly');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}