<?php

namespace App\Domain\Documents\Models;


use Surqlize\Model\Model;
use Surqlize\Attributes\Table;
use Surqlize\Attributes\Schema;
use App\Domain\Documents\Schemas\DocumentSectionTranslationSchema;


#[Table('document_section_translation')]
#[Schema(DocumentSectionTranslationSchema::class)]
class DocumentSectionTranslation extends Model
{

    protected string $id;

    protected string $documentId;

    protected string $workspaceId;

    protected string $section;

    protected string $title;

    protected string $translatedContent;

    protected string $summaryFa;

    protected array $keywords=[];

    protected array $embedding=[];



    public function __construct(array $data)
    {

        $this->id =
            $data['id'] ?? '';


        $this->documentId =
            $data['document_id'] ?? '';


        $this->workspaceId =
            $data['workspace_id'] ?? '';


        $this->section =
            $data['section'] ?? '';


        $this->title =
            $data['title'] ?? '';


        $this->translatedContent =
            $data['translated_content'] ?? '';


        $this->summaryFa =
            $data['summary_fa'] ?? '';


        $this->keywords =
            $data['keywords'] ?? [];


        $this->embedding =
            $data['embedding'] ?? [];

    }


}
