<?php

namespace Go2Flow\Ezport\Mail;

use Go2Flow\Ezport\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Spatie\MailcoachMailer\Concerns\UsesMailcoachMail;

class XmlImportError extends Mailable
{
    use Queueable, SerializesModels, UsesMailcoachMail;

    private Project $project;

    /**
     * Create a new message instance.
     */
    public function __construct(int $project, private string $string)
    {
        $this->project = Project::find($project);
    }

    public function build() : void
    {
        $this
            ->mailcoachMail('swi1-error', [
                'project' => $this->project->name,
                'time' => now()->format('Y-m-d H:i:s'),
                'content' => $this->string,
            ]);

    }
}
