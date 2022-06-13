<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MCIXController extends Controller
{
    public function index(){
        $token_update_time = env('MCIX_TOKEN_UPDATE_TIME');
        if(!$token_update_time){
            $this->check_auth();
        }else{
            // dd(env('MCIX_TOKEN_UPDATE_TIME'));
            $token_expired_date = Carbon::parse($token_update_time)->subDay(1);
            $nowDate = Carbon::now();
    
            $result = $token_expired_date->gt($nowDate);
            if(!$result){
                $this->check_auth();
            }
        }
        return view('mcix.index');
    }

    public function checkMCIX(){
        $file = request()->file('file');

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($file);
        $data_row=$spreadsheet->getSheet(0)->toArray();

        // echo count($data_row[0]);
        // echo '<br>';
        // echo count($data_row);
        // echo '<br>';
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        $i=1;
        unset($sheetData[0]);
        $data_arr = [];

        foreach ($sheetData as $row) {
            for ($j=0; $j < count($data_row[0]); $j++) { 
                if (isset($row[$j])) {
                    $search_nrc = $this->search_nrc($row[$j]);
                    if($search_nrc){
                        $check_credit_risk = $this->check_credit_report( $search_nrc->UniqueID);
                        if($check_credit_risk){
                            $data_arr[$i]['Name'] = $search_nrc->FullName;
                            $data_arr[$i]['Active'] = ($search_nrc->Active == 'Y') ? 'Active':'Inactive';
                            $data_arr[$i]['NRC'] = ($search_nrc->NRC);
                            $data_arr[$i]['al_mfi_str']=$check_credit_risk[0];
                            $data_arr[$i]['al_amount']=$check_credit_risk[1];
                            $data_arr[$i]['overlap_count']=$check_credit_risk[2];
                            $data_arr[$i]['dl_mfi_str']=$check_credit_risk[3];
                            $data_arr[$i]['dl_amount']=$check_credit_risk[4];
                            $data_arr[$i]['wof_amount']=$check_credit_risk[5];
                            $data_arr[$i]['wof_mfi_str']=$check_credit_risk[6];
                        }
                    }
                }
            }
            $i++;
        }
        $this->export_excel($data_arr);
               // Storage::disk('public')->put('test_'.strtotime(now()).'.json', json_encode($data));
        // echo "done";

    }
    public function export_excel($data){
        // $extension = 'xlsx';
        // $this->load->helper('download');  
        $fileName = 'mcix-'.time(); 
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Active');
        $sheet->setCellValue('B1', 'Active');
        $sheet->setCellValue('C1', 'Name');
        $sheet->setCellValue('D1', 'NRC');
        $sheet->setCellValue('E1', 'No of Overlap');
        $sheet->setCellValue('F1', 'Outstanding Amount');
        $sheet->setCellValue('G1', 'Outstanding MFIs Name');
        $sheet->setCellValue('H1', 'Delinquent Outstanding amount');
        $sheet->setCellValue('I1', 'Delinquent Outstanding MFIs  Name');
        $sheet->setCellValue('J1', 'WR Outstanding amount');
        $sheet->setCellValue('K1', 'WROutstanding MFIs  Name');
        $rowCount = 1;



        foreach ($data as $key=>$element) {
            $rowCount++;
            $sheet->setCellValue('A' . $rowCount, $rowCount-1);
            $sheet->setCellValue('B' . $rowCount, $data[$key]['Active']);
            $sheet->setCellValue('C' . $rowCount, $data[$key]['Name']);
            $sheet->setCellValue('D' . $rowCount, $data[$key]['NRC']);
            $sheet->setCellValue('E' . $rowCount, $data[$key]['overlap_count']);
            $sheet->setCellValue('F' . $rowCount, $data[$key]['al_amount']);
            $sheet->setCellValue('G' . $rowCount, $data[$key]['al_mfi_str']);
            $sheet->setCellValue('H' . $rowCount, $data[$key]['dl_amount']);
            $sheet->setCellValue('I' . $rowCount, $data[$key]['dl_mfi_str']);
            $sheet->setCellValue('J' . $rowCount, $data[$key]['wof_amount']);
            $sheet->setCellValue('K' . $rowCount, $data[$key]['wof_mfi_str']);
            
        }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $fileName = $fileName.'.xls';
    
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($fileName).'"');
        $writer->save('php://output');
    }

    public function check_auth(){
        $expire_date =  date('Y-m-d', strtotime(now(). ' + 14 days')); 
        $client = new \GuzzleHttp\Client();
        $mcix_response = $client->post(env('MCIX_URL').'/Token',
                                    [
                                        'headers' => 
                                        [
                                            'Content-Transfer-Encoding' => "application/x-www-form-urlencoded",
                                            'mcix-subscription-key' =>env('MCIX_SUB_KEY'),
                                            'mcix-subscription-id' =>env('MCIX_SUB_ID'),
                                        ],
                                        'form_params' => [
                                            'grant_type' => env('MCIX_GRANT'), 
                                            'userName' => env('MCIX_USERNAME'), 
                                            'password' => env('MCIX_PASS')
                                            
                                       ] 
                                    ],
                                    

                                );
        $mcix_response = json_decode($mcix_response->getBody()->getContents());
        $path = base_path('.env');

        if (file_exists($path)) {
            file_put_contents($path, str_replace(
                'MCIX_TOKEN='.env('MCIX_TOKEN'), 'MCIX_TOKEN='.$mcix_response->access_token, file_get_contents($path)
            ));
            file_put_contents($path, str_replace(
                'MCIX_TOKEN_UPDATE_TIME='.env('MCIX_TOKEN_UPDATE_TIME'), 'MCIX_TOKEN_UPDATE_TIME='.$expire_date, file_get_contents($path)
            ));
        }
        return true;
    }
    public function search_nrc($nrc){
        $client = new \GuzzleHttp\Client();
        $mcix_response = $client->post(env('MCIX_URL').'/api/Search/SimpleSearch',
                                    [
                                        'headers' => 
                                        [
                                            'Content-Transfer-Encoding' => "application/x-www-form-urlencoded",
                                            'mcix-subscription-key' =>env('MCIX_SUB_KEY'),
                                            'mcix-subscription-id' =>env('MCIX_SUB_ID'),
                                            'Authorization' => 'Bearer ' . env('MCIX_TOKEN'),    
                                        ],
                                        'form_params' => [
                                            'nrc' => $nrc
                                            
                                       ] 
                                    ],
                                    

                                );
        $mcix_response = json_decode($mcix_response->getBody()->getContents());
        // dd($mcix_response);
        if($mcix_response->Data){
            return $mcix_response->Data[0];
        }
        else{
            return false;
        }
    }

    public function check_credit_report($uniq_id){
        $client = new \GuzzleHttp\Client();
        $mcix_response = $client->get(env('MCIX_URL').'/api/Dashboard/GetCreditReport?uniqueId='.$uniq_id,
            [
                'headers' => 
                [
                    'Content-Transfer-Encoding' => "application/x-www-form-urlencoded",
                    'mcix-subscription-key' =>env('MCIX_SUB_KEY'),
                    'mcix-subscription-id' =>env('MCIX_SUB_ID'),
                    'Authorization' => 'Bearer ' . env('MCIX_TOKEN'),    
                ]
            ],    

        );
        $mcix_response = json_decode($mcix_response->getBody()->getContents());
        $al_mfi_str='';
        $al_amount = 0;
        $dl_mfi_str = '';
        $dl_amount = 0;
        $wof_mfi_str = '';
        $wof_amount = 0;
        $al_mfi_dup_arr = [];
        $wl_mfi_dup_arr = [];
        $dl_mfi_dup_arr =[];
        $overlap_count = 0;
       
        if($mcix_response->Data){
            if($mcix_response->Data->ActiveLoans){
                $loan_data = $mcix_response->Data->ActiveLoans;
                for($i=0;$i<count($loan_data);$i++){

                    if($loan_data[$i]->Institution!='Maha'){
                        $al_amount+=($loan_data[$i]->PrincipalOutstandingAmount!="")?$loan_data[$i]->PrincipalOutstandingAmount:0;
                        if(!in_array($loan_data[$i]->Institution,$al_mfi_dup_arr)){
                            $al_mfi_str.=$loan_data[$i]->Institution.',';
                            array_push($al_mfi_dup_arr, $loan_data[$i]->Institution);
                            $overlap_count++;
                        }
                        if($loan_data[$i]->DaysInDelay>0){
                            $dl_amount+=($loan_data[$i]->PrincipalOutstandingAmount!="")?$loan_data[$i]->PrincipalOutstandingAmount:0;
                            if(!in_array($loan_data[$i]->Institution,$dl_mfi_dup_arr)){
                                $dl_mfi_str.=$loan_data[$i]->Institution.',';
                                array_push($dl_mfi_dup_arr, $loan_data[$i]->Institution);
                            }
                        }
                    }
                }
            }


            if($mcix_response->Data->WriteOffLoans){
                $wlloan_data = $mcix_response->Data->WriteOffLoans;
                for($i=0;$i<count($wlloan_data);$i++){
                    $wof_amount+=($wlloan_data[$i]->PrincipalWriteOffAmount!="")?$wlloan_data[$i]->PrincipalWriteOffAmount:0;
                    if(!in_array($wlloan_data[$i]->Institution,$wl_mfi_dup_arr)){
                        $wof_mfi_str.=$wlloan_data[$i]->Institution.',';
                        array_push($wl_mfi_dup_arr, $wlloan_data[$i]->Institution);
                    }
                }
                
            }

            return [$al_mfi_str,$al_amount,$overlap_count,$dl_mfi_str,$dl_amount,$wof_amount,$wof_mfi_str];
            
        }
        else{
            return false;
        }
    }

    
    
}

