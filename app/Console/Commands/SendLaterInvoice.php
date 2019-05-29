<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InvoiceEmailQueue;
use App\Jobs\SendInvoiceEmail;

class SendLaterInvoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sendlatter:invoice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Invoice at specificed Time';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info(date('Y-m-d H:i:s') . ' Running InvoiceQueue...');
        $invoicesQueue = InvoiceEmailQueue::where("send_at","<=",date("Y-m-d H:i:s"))->get();
        $this->info(count($invoicesQueue) . ' Queue Found...');
        foreach($invoicesQueue as $queue){
            
            $template = [
                "subject"=>$queue->template->subject,
                "body"=>$queue->template->body
            ];
            $queue->invoice->is_public = true;
            $queue->invoice->save();
            dispatch(new SendInvoiceEmail($queue->invoice,$queue->invoice->user_id, $queue->template_id, $template));
            
            $this->info($queue->invoice->invoice_number . ' notification '.$queue->template->name.' Sent...');
            $queue->delete();  
        }
        $this->info("Done");
        
    }
}
