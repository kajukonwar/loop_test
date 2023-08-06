<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Services\CustomerService;

class ImportCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports sample customer data';

    /**
     * Execute the console command.
     */
    public function handle(CustomerService $customer)
    {
        try {
            $imported_records = $customer->importToDb();
            $this->info('Total '.$imported_records.' records were imported successfuly');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
