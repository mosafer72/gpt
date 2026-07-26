<?php

namespace App\Domain\Documents\Models;


use App\Domain\Documents\Schemas\DocumentStructureSchema;
use Surqlize\Attributes\Schema;
use Surqlize\Attributes\Table;
use Surqlize\Model\Model;


#[Table('document_structure')]
#[Schema(DocumentStructureSchema::class)]
class DocumentStructure extends Model
{


    private string $id = '';

    private string $documentId = '';

    private string $workspaceId = '';

    private array $title = [];

    private array $sections = [];

    private ?string $createdAt = null;




    public function __construct(
        array $data
    ) {


        $this->id =
            (string)
            ($data['id'] ?? '');



        $this->documentId =
            (string)
            ($data['document_id'] ?? '');



        $this->workspaceId =
            (string)
            ($data['workspace_id'] ?? '');



        $this->title =
            $data['title']
            ??
            [];



        $this->sections =
            $data['sections']
            ??
            [];



        $this->createdAt =
            $data['created_at']
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







    public function getTitle(): array
    {
        return $this->title;
    }







    public function getSections(): array
    {
        return $this->sections;
    }







    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }







    /**
     * Get important sections only
     */
    public function getImportantSections(): array
    {

        return array_values(
            array_filter(
                $this->sections,
                fn(array $section) =>
                    ($section['importance'] ?? null) === 'high'
            )
        );

    }







    /**
     * Find section by name
     */
    public function findSection(
        string $name
    ): ?array {


        foreach($this->sections as $section)
        {

            if(
                ($section['name'] ?? null)
                ===
                $name
            )
            {
                return $section;
            }

        }


        return null;

    }



}
