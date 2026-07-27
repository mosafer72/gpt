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


    private string $id = '';

    private string $documentId = '';

    private string $workspaceId = '';

    private string $section = '';

    private string $title = '';

    private string $translatedContent = '';

    private string $summaryFa = '';

    private array $keywords = [];

    private array $embedding = [];

    private ?string $createdAt = null;

    private ?string $updatedAt = null;







    public function __construct(
        array $data = []
    )
    {


        $this->id =
            (string)
            (
                $data['id']
                ??
                ''
            );




        $this->documentId =
            (string)
            (
                $data['document_id']
                ??
                ''
            );




        $this->workspaceId =
            (string)
            (
                $data['workspace_id']
                ??
                ''
            );




        $this->section =
            (string)
            (
                $data['section']
                ??
                ''
            );




        $this->title =
            (string)
            (
                $data['title']
                ??
                ''
            );




        $this->translatedContent =
            (string)
            (
                $data['translated_content']
                ??
                ''
            );




        $this->summaryFa =
            (string)
            (
                $data['summary_fa']
                ??
                ''
            );






        $this->keywords =

            is_array(
                $data['keywords']
                ??
                null
            )

            ?

            $data['keywords']

            :

            [];







        $this->embedding =

            is_array(
                $data['embedding']
                ??
                null
            )

            ?

            array_map(
                'floatval',
                $data['embedding']
            )

            :

            [];







        $this->createdAt =
            $data['created_at']
            ??
            null;




        $this->updatedAt =
            $data['updated_at']
            ??
            null;


    }









    public function getId(): string
    {
        return $this->id;
    }






    public function getDocumentId(): string
    {
        return $this->documentId;
    }






    public function getWorkspaceId(): string
    {
        return $this->workspaceId;
    }






    public function getSection(): string
    {
        return $this->section;
    }






    public function getTitle(): string
    {
        return $this->title;
    }






    public function getTranslatedContent(): string
    {
        return $this->translatedContent;
    }






    public function getSummaryFa(): string
    {
        return $this->summaryFa;
    }






    public function getKeywords(): array
    {
        return $this->keywords;
    }






    public function getEmbedding(): array
    {
        return $this->embedding;
    }






    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }






    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }









    /*
    |--------------------------------------------------------------------------
    | Translation Helpers
    |--------------------------------------------------------------------------
    */






    public function hasTranslation(): bool
    {

        return

            trim(
                $this->translatedContent
            )
            !==
            '';

    }







    public function hasEmbedding(): bool
    {

        return

            !empty(
                $this->embedding
            );

    }





}
