<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Models\Sponsor;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConfirmWithdrawAdminUnitTest extends TestCase
{
    use RefreshDatabase;

    protected $token;
    protected $headers;
    protected $validData;
    protected $authUser;
    protected $event;
    protected $sponsor;
    protected $transaction;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Simulate user login and get token
        $response = $this->post('/api/login', [
            'email' => 'ab@gmail.com',
            'password' => 'adam1234'
        ]);

        $this->token = $response->json('token');
        $this->authUser = $response->json('user.id');
        $this->headers = [
            'Authorization' => 'Bearer ' . $this->token,
        ];

        // Create event
        $this->event = Event::create([
            'id_user' => $this->authUser,
            'name' => 'Semarak Kemerdekaan',
            'description' => 'Semarak kemerdekaan merupakan bisnis plan tahunan yang diadakan di poliwangi oleh UKM KWU',
            'email' => 'anggotakwu@gmail.com',
            'location' => 'https://maps.app.goo.gl/kroonKXRdun2SfWo7',
            'proposal' => "proposal/proposal.pdf",
            'start_date' => '2024-12-07',
            'venue_name' => 'Poliwangi',
            'image' => 'image/poster.jpg',
            'fund1' => '10000000',
            'slot1' => '2',
            'fund2' => '7500000',
            'slot2' => '3',
            'fund3' => '5000000',
            'slot3' => '4',
            'fund4' => '2500000',
            'slot4' => '5'
        ]);

        // Create sponsor
        $this->sponsor = Sponsor::create([
            'name' => 'Sponsor Event Musik',
            'email' => 'sponsor@musik.com',
            'description' => 'Sponsor untuk event musik',
            'address' => 'Jl. Musik No.1',
            'id_category' => 1,
            'max_submission_date' => 30,
            'image' => 'image.jpg',
            'id_user' => 2
        ]);

        // Create transaction
        $this->transaction = Transaction::create([
            'id_event' => $this->event->id,
            'id_sponsor' => $this->sponsor->id,
            'id_status' => 1,
            'id_user' => $this->authUser,
            'id_level' => 1,
            'comment' => 'semoga eventnya sukses ya',
            'no_rek' => '08942288383',
            'bank_name' => 'bni',
            'account_name' => 'falen',
            'id_payment_status' => 3,
            'id_withdraw_status' => 2,
            'payment_date' => '2024-04-05',
            'withdraw_date' => '2024-04-05',
            'total_fund' => '500000'
        ]);
    }

    public function test_confirm_withdraw_with_valid_data()
    {
        $response = $this->post('/api/admin/withdraw', [
            'id' => $this->transaction->id,
            'id_withdraw_status' => 3,
            'total_fund' => $this->transaction->total_fund, // Ensure fund amount matches
        ], $this->headers);

        $response->assertStatus(201)->assertJsonFragment([
            'message' => 'Konfirmasi pencairan dana berhasil',
            'id_withdraw_status' => 3,
        ]);
    }

    public function test_confirm_withdraw_with_unknown_id_transaction()
    {
        $response = $this->post('/api/admin/withdraw', [
            'id' => '29n29',  // Invalid transaction ID
            'id_withdraw_status' => 3,
            'total_fund' => 500000, // Correct fund amount
        ], $this->headers);

        $response->assertStatus(404); // Not found
    }

    public function test_confirm_withdraw_with_incorrect_fund_amount()
    {
        $response = $this->post('/api/admin/withdraw', [
            'id' => $this->transaction->id,
            'id_withdraw_status' => 3,
            'total_fund' => 1000000, // Invalid fund amount
        ], $this->headers);

        $response->assertStatus(400)->assertJsonFragment([
            'error' => 'Konfirmasi pencairan dana gagal karena jumlah tidak sesuai',
        ]);
    }
}
