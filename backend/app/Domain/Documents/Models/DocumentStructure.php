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







    public function getDocumentId(): string
    {

        return $this->documentId;

    }







    public function getWorkspaceId(): string
    {

        return $this->workspaceId;

    }







    public function getSections(): array
    {

        return $this->sections;

    }







    public function getTitle(): array
    {

        return $this->title;

    }


}
