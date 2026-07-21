<?php

declare(strict_types=1);

namespace UniPotsdam\Orcid\Dto;

enum WorkType: string
{
    case JournalArticle = 'journal-article';
    case ConferencePaper = 'conference-paper';
    case ConferenceOutput = 'conference-output';
    case ConferencePresentation = 'conference-presentation';
    case ConferenceProceedings = 'conference-proceedings';
    case DissertationThesis = 'dissertation-thesis';
    case Book = 'book';
    case BookChapter = 'book-chapter';
    case WorkingPaper = 'working-paper';
    case Dissertation = 'dissertation';
    case Report = 'report';
    case Preprint = 'preprint';
    case Other = 'other';

    public static function fromOrcid(?string $type): self
    {
        return match ($type) {
            'journal-article' => self::JournalArticle,
            'conference-paper' => self::ConferencePaper,
            'conference-output' => self::ConferenceOutput,
            'conference-presentation' => self::ConferencePresentation,
            'conference-proceedings' => self::ConferenceProceedings,
            'dissertation-thesis' => self::DissertationThesis,
            'book' => self::Book,
            'book-chapter' => self::BookChapter,
            'working-paper' => self::WorkingPaper,
            'dissertation' => self::Dissertation,
            'report' => self::Report,
            'preprint' => self::Preprint,
            default => self::Other,
        };
    }
}
