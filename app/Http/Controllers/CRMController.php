<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CRMController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api']);
        header('Accept: application/json');
        header('Content-Type: application/json');
    }
    public function createCRMRecord(Request $req)
    {
        try {
            $tableName = $req->input('label');
            $properties = $req->input('properties');
            $id = $req->input('id');
            $columns  = array_keys($properties);
            $edges = $req->input('edges');
            // Check if table exists in database
            if (Schema::hasTable($tableName)) {
                $existingColumns = Schema::getColumnListing($tableName);
                $extraColumns = array_diff($columns, $existingColumns);
                foreach ($extraColumns as $column) {
                    Schema::table($tableName, function ($table) use ($column, $properties) {
                        $type  = gettype($properties[$column]);
                        $table->{$type}($column)->nullable();
                    });
                }
                $existing = DB::table($tableName)->where('id', "=", $id)->count();
                if ($existing > 0) {
                    $properties['updated_at'] = date('Y-m-d H:i:s');
                    DB::table($tableName)->where('id', "=", $id)->update($properties);
                } else {
                    $properties['id'] = Str::uuid()->toString();
                    $properties['created_at'] = date('Y-m-d H:i:s');
                    $properties['updated_at'] = date('Y-m-d H:i:s');
                    DB::table($tableName)->insert($properties);
                }
            } else {
                // Create new table with the columns
                Schema::create($tableName, function ($table) use ($columns, $properties) {
                    $table->string('id')->primary();
                    $table->boolean('isDeleted')->default(false);
                    foreach ($columns as $columnName) {
                        $type  = gettype($properties[$columnName]);
                        $table->{$type}($columnName)->nullable();
                    }
                    $table->timestamps();
                });
                $properties['id'] = Str::uuid()->toString();
                $properties['created_at'] = date('Y-m-d H:i:s');
                $properties['updated_at'] = date('Y-m-d H:i:s');
                DB::table($tableName)->insert($properties);
            }
            if (!empty($edges) && count($edges) > 0) {
                $this->createEdge($edges, $properties['id'], $tableName);
            }
            return $properties;
        } catch (Exception $e) {
            return Response::json([
                'error' => "Something went wrong"
            ], 500);
        }
    }
    public function createEdge($edges, $id, $sourceLabel)
    {
        if (!Schema::hasTable('edges')) {
            Schema::create('edges', function ($table) {
                $table->string('edge_id')->primary();
                $table->string('source_id');
                $table->string('destination_id');
                $table->string('source_label');
                $table->string('destination_label');
                $table->string('edge_label');
                $table->timestamps();
            });
        }
        $data = [];
        foreach ($edges as $edge) {
            $tempData = [];
            $tempData['edge_id'] = Str::uuid()->toString();
            $tempData['source_id'] = $id;
            $tempData['destination_id'] = $edge['id'];
            $tempData['source_label'] = strtolower($sourceLabel);
            $tempData['destination_label'] = strtolower($edge['label']);
            $tempData['edge_label'] = $edge['edgeLabel'];
            $tempData['created_at'] = date('Y-m-d H:i:s');
            $tempData['updated_at'] = date('Y-m-d H:i:s');
            array_push($data, $tempData);
        }
        DB::table('edges')->insert($data);
    }


    public function getCRMRecord(Request $req)
    {
        DB::enableQueryLog();
        $sourceLabel = strtolower($req->input('sourceLabel'));
        $destinations = $req->input('destinations');
        $sourceId = $req->input('sourceId');
        $showSQL = $req->input('showSQL');
        $data = [];

        if (Schema::hasTable($sourceLabel) && !empty($destinations) && count($destinations) > 0) {

            $data =  DB::table('edges')->select('edge_label', 'edge_id');
            $data = $data->join($sourceLabel, "edges.source_id", "=", "{$sourceLabel}.id");
            $fields = ["$sourceLabel.*"];

            foreach ($destinations as $destination) {
                if (Schema::hasTable(strtolower($destination['label'])) && !empty($destination['fields']) && count($destination['fields']) > 0) {
                    $data = $data->join($destination['label'], "edges.destination_id", "=", "{$destination['label']}.id");

                    foreach ($destination['fields'] as $field) {
                        array_push($fields, "{$destination['label']}.$field as {$destination['label']}__$field");
                    }
                }
            }
            $data = $data->select($fields);
        } elseif (Schema::hasTable($sourceLabel)) {
            $data =  DB::table($sourceLabel);
        }

        if (!empty($sourceId)) {
            $data = $data->where("$sourceLabel.id", "=", $sourceId);
        }
        $data = $data->where("isDeleted", "=", false);
        if (isset($showSQL) && $showSQL == true) {
            return $data->toSql();
        }
        return $data->get();
    }
    public function getCRMRecordWithMultiLabel(Request $req)
    {
        DB::enableQueryLog();
        $sourceLabels = $req->input('sourceLabels');
        $showSQL = $req->input('showSQL');
        $dataSet = array();
        foreach ($sourceLabels as $sourceLabel) {
            $data = [];
            if (Schema::hasTable(strtolower($sourceLabel))) {
                $data =  DB::table(strtolower($sourceLabel));
                $data = $data->where("isDeleted", "=", false);
                if (isset($showSQL) && $showSQL == true) {
                    return $data->toSql();
                }
            } else {
                $dataSet[$sourceLabel] =  [];
            }
        }

        return $dataSet;
    }
}
