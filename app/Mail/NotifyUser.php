<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotifyUser extends Mailable
{
    use Queueable, SerializesModels;
    public $name;

    /**
     * Create a new message instance.
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        try {
            $subject = "Fiber One Network Coverage";
            return $this->from('coveragemap@fob.ng', 'Fiber One')
            ->subject($subject)
            ->view('emails.notifyuser')
            ->with(['message' => $this])
            ->with('name', $this->name);

        } catch (Exception $e) {
            return "Server Error! Email was not send";
        }
    }
}
