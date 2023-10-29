<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
// use App\Libraries\Common\Logs;
use Illuminate\Support\Facades\DB;

class RecDataCommand extends Command
{
    protected $signature = 'recData:copy';

    protected $description = 'Copy data from one table to another and delete old data';

    public function handle()
    {
        // Get the current date and date 7 days ago
        $currentDate = Carbon::now();
        $oneWeekAgo = Carbon::now()->subDays(1);
      
        // Assuming you have two tables: 'source_table' and 'destination_table'
        $sourceTable = 'recharge';
        $destinationTable = 'recharge_backup';

        // Select data from the source table before 7 days
        $Chunks = 500;
        $totalRecords = DB::table($sourceTable)->where('created_at', '<', $oneWeekAgo)->count();
        $logsData = [
            'dir' => 'datacopy',
            'data'=> [
                'message' => 'Copy data from '.$sourceTable.' to '.$destinationTable.' started. Total records '.$totalRecords
            ]
        ];
        //Logs::dataCopyLogs($logsData);
        if($totalRecords > 0){
            $numChunks = ceil($totalRecords/$Chunks);
            $offset = 0;
            for ($i=0; $i < $numChunks; $i++) { 
                $dataForInsert = DB::table($sourceTable)->where('created_at', '<', $oneWeekAgo)->skip($offset)->take($Chunks)->get()->toArray();
                foreach ($dataForInsert as $key => $dataArray) {
                  DB::table($destinationTable)->insert((array) $dataArray);
                }
             $offset += $Chunks;
            }
        
            // Delete the copied data from the source table
            DB::table($sourceTable)->where('created_at', '<', $oneWeekAgo)->delete();
        }

        $logsData = [
            'dir' => 'datacopy',
            'data'=> [
                'message' => 'Data Copied from '.$sourceTable.' to '.$destinationTable.'. Total records '.$totalRecords
            ]
        ];
        //Logs::dataCopyLogs($logsData);
    }
}