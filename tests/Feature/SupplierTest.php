<?php

namespace Tests\Feature;

use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    /**
     * In the task we need to calculate amount of hours suppliers are working during last week for marketing.
     * You can use any way you like to do it, but remember, in real life we are about to have 400+ real
     * suppliers.
     *
     * @return void
     */
    public function testCalculateAmountOfHoursDuringTheWeekSuppliersAreWorking()
    {
        $response = $this->get('/api/suppliers');

        $data = $response["data"]["suppliers"];
        $hours = NAN;
        $amount = 0;
        for($i = 0; $i < count($data); $i ++){
            $amount += $this->getWorkTime(substr($data[$i]["mon"], 5));
            $amount += $this->getWorkTime(substr($data[$i]["tue"], 5));
            $amount += $this->getWorkTime(substr($data[$i]["wed"], 5));
            $amount += $this->getWorkTime(substr($data[$i]["thu"], 5));
            $amount += $this->getWorkTime(substr($data[$i]["fri"], 5));
            $amount += $this->getWorkTime(substr($data[$i]["sat"], 5));
            $amount += $this->getWorkTime(substr($data[$i]["sun"], 5));
        }
        $hours = intval($amount/60);

        $response->assertStatus(200);

        $this->assertEquals(136, $hours,
            "Our suppliers are working X hours per week in total. Please, find out how much they work..");
    }

    public function getWorkTime($time){
        $ary = explode(",", $time);
        $amount = 0;
        for($j = 0; $j < count($ary); $j ++){
            if(trim($ary[$j]) == "") continue;
            $range = explode("-", trim($ary[$j]));
            $st = explode( ":", $range[0]);
            $ed = explode(":", $range[1]);
            $amount += (intval($ed[0]) - intval($st[0])) * 60 + intval($ed[1]) - intval($st[1]);

        }
        return $amount;
    }

    /**
     * Save the first supplier from JSON into database.
     * Please, be sure, all asserts pass.
     *
     * After you save supplier in database, in test we apply verifications on the data.
     * On last line of the test second attempt to add the supplier fails. We do not allow to add supplier with the same name.
     */
    public function testSaveSupplierInDatabase()
    {
        Supplier::query()->truncate();
        $responseList = $this->get('/api/suppliers');
        $supplier = \json_decode($responseList->getContent(), true)['data']['suppliers'][0];

        $response = $this->post('/api/suppliers', ["supplier"=>$supplier]);



        $response->assertStatus(204);
        $this->assertEquals(1, Supplier::query()->count());
        $dbSupplier = Supplier::query()->first();
        $this->assertNotFalse(curl_init($dbSupplier->url));
        $this->assertNotFalse(curl_init($dbSupplier->rules));
        $this->assertGreaterThan(4, strlen($dbSupplier->info));
        $this->assertNotNull($dbSupplier->name);
        $this->assertNotNull($dbSupplier->district);


        $response = $this->post('/api/suppliers', ["supplier"=>$supplier]);
        $response->assertStatus(422);
    }
}
