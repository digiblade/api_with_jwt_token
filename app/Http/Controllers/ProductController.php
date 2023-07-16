<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductController extends Controller
{
    public function getProductDetails(Request $req)
    {
        $pageId = $req->input("pageId");
        $pageTable = 'page-config';
        $fieldTable = 'field-config';
        $templateTable = 'template-config';
        $response = array();
        if (Schema::hasTable($pageTable)) {
            $pageDetails = DB::table('page-config')->where('id', "=", $pageId)->where('isDeleted', "=", false)->first();
            if ($pageDetails == null) {
                return response()->json([
                    "error" => "Page Id is not valid"
                ], 404);
            } else {
                $fieldDetails = [];
                $templateDetails = [];
                if ($fieldTable != null) {
                    $fieldDetails = $this->getDataFromTable($fieldTable, where: ["pageId", "=", $pageId]);
                }
                if ($templateTable != null) {
                    $templateDetails = $this->getDataFromTable($fieldTable, where: ["pageId", "=", $pageId]);
                }
                $response['pageDetails']   = $pageDetails;
                $response['fields'] = $fieldDetails;
                $response['templates'] = $templateDetails;
            }
            return $response;
        }
    }
    public function getDataFromTable($tableName, $where = [])
    {
        $response = [];
        if (Schema::hasTable($tableName)) {
            $tableDetails = DB::table($tableName)->where('isDeleted', "=", false);
            if (count($where) == 3) {
                $tableDetails = $tableDetails->where($where[0], $where[1], $where[2]);
            }
            $tableDetails = $tableDetails->get();
            array_push($response, $tableDetails);
        }
        return $response;
    }
}
