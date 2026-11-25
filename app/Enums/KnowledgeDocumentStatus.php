<?php

namespace App\Enums;

enum KnowledgeDocumentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
