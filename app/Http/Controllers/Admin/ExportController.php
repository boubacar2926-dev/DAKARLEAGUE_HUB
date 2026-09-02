<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Competition;
use App\Models\Document;
use App\Services\StandingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class ExportController extends Controller
{
    public function calendar(Competition $competition): Response
    {
        $this->authorize('view', $competition);

        $matches = $competition->matches()
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('round')
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy('round');

        $pdf = Pdf::loadView('pdf.calendar', compact('competition', 'matches'))->setPaper('a4');
        $downloadName = Str::slug("calendrier-{$competition->name}-{$competition->season}").'.pdf';

        // ->output() ne doit être appelé qu'une seule fois sur une même instance Dompdf :
        // un second appel (via ->download() par exemple) resérialise un PDF déjà rendu et
        // corrompt les flux compressés (polices en glyphes manquants, fichier ~2x plus lourd).
        $contents = $pdf->output();

        $this->recordExport($competition, Document::TYPE_CALENDAR_PDF, "Calendrier — {$competition->name}", $contents);

        return $this->downloadResponse($contents, $downloadName);
    }

    public function standings(Competition $competition, StandingsService $standingsService): Response
    {
        $this->authorize('view', $competition);

        $standings = $standingsService->calculate($competition);

        $pdf = Pdf::loadView('pdf.standings', compact('competition', 'standings'))->setPaper('a4');
        $downloadName = Str::slug("classement-{$competition->name}-{$competition->season}").'.pdf';

        $contents = $pdf->output();

        $this->recordExport($competition, Document::TYPE_STANDINGS_PDF, "Classement — {$competition->name}", $contents);

        return $this->downloadResponse($contents, $downloadName);
    }

    private function downloadResponse(string $contents, string $filename): Response
    {
        return new Response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition('attachment', $filename),
            'Content-Length' => strlen($contents),
        ]);
    }

    /**
     * Les PDF exportés sont stockés sur le disque privé (jamais servis directement par le
     * serveur web) avec un nom non prévisible : une compétition en brouillon ou non publiée
     * ne doit jamais être accessible via une URL devinée à partir de son nom/saison.
     */
    private function recordExport(Competition $competition, string $type, string $title, string $contents): void
    {
        $path = 'documents/'.Str::random(40).'.pdf';
        Storage::disk('local')->put($path, $contents);

        $document = Document::create([
            'competition_id' => $competition->id,
            'uploaded_by' => request()->user()->id,
            'type' => $type,
            'title' => $title,
            'file_path' => $path,
        ]);

        ActivityLog::record('document.exported', $document, "Export PDF : {$title}", competitionId: $competition->id);
    }
}
