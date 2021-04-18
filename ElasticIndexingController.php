<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace console\controllers;

use yii\console\Controller;
use yii\elasticsearch\Query;
use app\models\AmtAUCompany;
use app\models\AmtFRCompany;
use app\models\AmtKRCompany;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ElasticIndexingController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     * @param string $message the message to be echoed.
     */
    public function actionDoIndexing($country_code = 'au', $reindex = false)
    {
    	
    	/*The algorithm in brief*/
        //if the compnay is not in the processed list
        	//get company_name, and web
        	//put it in the processed list
        	//get table name
	        //get all distinct category_l1 from table and company
	        //get all distinct category_l2 from table and company
	        //get all distinct category_l3 from table and company
	        //get all distinct category_l4 from table and company
	        //get business description and other info
	        //form the data with category JSON
	        //insert index
	        //update all records to is_indexed=1 where company_name, and web same
    	
    	echo "\n\rcountry_code =".$country_code."\n\r";
        $query = new Query;
        $connection = \Yii::$app->getDb();
        $table_name = "amt_au_company";
        $command = $query->createCommand();
        $rs = $command->о($country_code);
        if(!isset($rs) && empty($rs)) {
        	$rs = $command->createIndex($country_code);
        	if(isset($rs) && !empty($rs))
        		print "Index is created\n\r";
        }
        else {
        	print "Index already exists\n\r";
        }
        $index = $country_code;
        $type = 'company';
        $count = 0;
        switch ($country_code) {
        	//australia
        	case 'au': $count = AmtAUCompany::find()->count(); $datamodel = new AmtAUCompany(); $table_name = 'amt_au_company';
        	break;
        	//france
        	case 'fr': $count = AmtFRCompany::find()->count(); $datamodel = new AmtFRCompany();$table_name = 'amt_fr_company';
        	break;
        	//korea
        	case 'kr': $count = AmtKRCompany::find()->count(); $datamodel = new AmtKRCompany();$table_name = 'amt_kr_company';
        	break;
        }
         
        $chunk = ceil($count/10);
        print "Total data chunk: ".$chunk."\n\r";
        for($i=0;$i<$chunk;$i++){
        	$lower_limit = $i*10;
        	$upper_limit = 10;//($i+1)*100;
        	print "Indexing Range: ".$lower_limit." - ".$upper_limit*($i+1)."\n\r";
        	$companies = "";
        	if(!$reindex) {
        		$companies = $datamodel->find()
        					->where('amt_rank != :rank_score and is_indexed = :index_status', ['rank_score'=>0, 'index_status'=>0])
        					->orderBy('company_key')
        					->offset($lower_limit)
        					->limit($upper_limit)->all();
        			}
        	else {
        		$companies = $datamodel->find()
        					->orderBy('company_key')
        					->offset($lower_limit)
        					->limit($upper_limit)->all();
        			}

        	print "total companies: ".sizeof($companies)."\n\r"; 
        	print "Start Key: ".$companies[0]->company_key;
        	print "\tEnd Key: ".$companies[sizeof($companies)-1]->company_key."\n\r";
        	$company_processed = array();
        	foreach($companies as $acompany){
        	  if($acompany->is_indexed !=1){
        		$company_name = $acompany->company_name;
        		$web = $acompany->web;
        		$company_str = $company_name." ".$web;
        		if(!in_array($company_str, $company_processed)){
        			print "Indexing :".$company_str."\n\r";
        			array_push($company_processed, $company_str);
        			$business_description = isset($acompany->business_description) ? $acompany->business_description : $acompany->company_description;
        			$amt_rank =$acompany->amt_rank;
        			$company_key = $acompany->company_key;
        			$category1 = [];
        			$category2 = [];
        			$category3 = [];
        			$category4 = [];
        			$sqlcommand = $connection->createCommand("SELECT distinct(category_l1) from $table_name where company_name = '$company_name' and web = '$web'");
        			$result = $sqlcommand->queryAll();
        			if(!empty($result) && isset($result)){
        			foreach($result as $id=>$row){
        				if(!empty($row) && isset($row))
        				foreach($row as $id2=>$val){
        					array_push($category1,$val);
        					//print "$val\n\r";
        				}
        			}
        			if(!empty($category1))
        				print "Category 1 extraction.....OK\n\r";
        			}
        			else
        				print "Category 1 is empty......\n\r";
        			
        			$sqlcommand = $connection->createCommand("SELECT distinct(category_l2) from $table_name where company_name = '$company_name' and web = '$web'");
        			$result = $sqlcommand->queryAll();
        			if(!empty($result) && isset($result)){
        			foreach($result as $id=>$row){
        				if(!empty($row) && isset($row))
        				foreach($row as $id2=>$val){
        					array_push($category2,$val);
        					//print "$val\n\r";
        				}
        			}
        			if(!empty($category2))
        				print "Category 2 extraction.....OK\n\r";
        			}
        			else
        				print "Category 2 is empty....\n\r";
        			
        			$sqlcommand = $connection->createCommand("SELECT distinct(category_l3) from $table_name where company_name = '$company_name' and web = '$web'");
        			$result = $sqlcommand->queryAll();
        			if(!empty($result) && isset($result)){
        			foreach($result as $id=>$row){
        				if(is_array($row) && !empty($row))
        				foreach($row as $id2=>$val){
        					array_push($category3,$val);
        					//print "$val\n\r";
        				}
        			}
        			if(!empty($category3))
        				print "Category 3 extraction.....OK\n\r";
        			}
        			else
        				print "Category 3 is empty....\n\r";
        			
        			$sqlcommand = $connection->createCommand("SELECT distinct(category_l4) from $table_name where company_name = '$company_name' and web = '$web'");
        			$result = $sqlcommand->queryAll();
        			
        			if(!empty($result) && isset($result)){
        			foreach((array)$result as $id=>$row){
        				if(is_array($row) && !empty($row)){
        				foreach((array)$row as $id2=>$val){
        					array_push($category4,$val);
        					//print "$val\n\r";
        				}
        			}
        			}
        			if(!empty($category4))
        				print "Category 4 extraction.....OK\n\r";
        			}
        			else
        				print "Category 4 is empty.....\n\r";
        			
        			//saving the data to the index
        			$data = [
        					"company_key"=> $company_key,
        					"category_l1"=>isset($category1) ? json_encode($category1) : $acompany->category,
        					"category_l2"=>isset($category2) ? json_encode($category2) : $acompany->address,
        					"category_l3"=>isset($category3) ? json_encode($category3) : $acompany->suburb,
        					"category_l4"=>isset($category4) ? json_encode($category4) : $acompany->state,
        					"company_name"=>$company_name,
        					"web" => $web,
        					"business_description"=>isset($business_description) ? $business_description : $acompany->company_description,
        					"amt_rank"=>$amt_rank,
        			];
        			
        			$command->insert( $index, $type, $data, $id = null, $options = [] );
        			$data = "";
        			$targetcompanies = $datamodel->find()->where(['company_name' => $company_name, 'web'=>$web])->all();
        			print sizeof($targetcompanies)." records are set to is_indexed = TRUE\n\r";
        			foreach ($targetcompanies as $id=>$target){
        				$target->is_indexed = 1;
        				$target->save();
        			}
        			$targetcompanies="";
 
        		}
        		else {
        			print "The company is already indexed....OK\n\r";
        		}
        	}
        		else {
        			print "The record is already indexed....OK\n\r";
        		}
        		 
        	}
        	
        	 
        }
        
    }
}
