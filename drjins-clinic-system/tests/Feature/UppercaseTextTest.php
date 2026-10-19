<?php

namespace Tests\Feature;

use App\Http\Middleware\UppercaseText;
use Illuminate\Http\Request;
use Tests\TestCase;

class UppercaseTextTest extends TestCase
{
    public function test_normalizes_text_without_changing_credentials_or_structured_values(): void
    {
        $request = Request::create('/patients', 'POST', [

            'first_name' => 'José',
            'last_name' => 'dela cruz',
            'address' => 'Abuyog, Leyte',

            'assessment' => 'Follow up in one week.',
            'password' => 'MixedCase123',

            'email' => 'Person@example.com',
            'status' => 'Approved',
            'sex' => 'Female',

            'birthdate' => '2020-01-01',
            'request_key' => 'abc-def',

        ]);
        (new UppercaseText)->handle($request, fn () => response('OK'));
        $this->assertSame('JOSÉ', $request->first_name);
        $this->assertSame('DELA CRUZ', $request->last_name);
        $this->assertSame('ABUYOG, LEYTE', $request->address);
        $this->assertSame('FOLLOW UP IN ONE WEEK.', $request->assessment);
        foreach ([
            'password' => 'MixedCase123',
            'email' => 'Person@example.com',
            'status' => 'Approved',
            'sex' => 'Female',
            'birthdate' => '2020-01-01',
            'request_key' => 'abc-def',
        ] as $field => $value) {
            $this->assertSame($value, $request->input($field));
        }
    }
}
