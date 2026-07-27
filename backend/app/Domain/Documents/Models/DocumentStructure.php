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

    private array $statistics = [];

    private array $metadata = [];

    private ?string $createdAt = null;

    private ?string $updatedAt = null;







    public function __construct(
        array $data = []
    ) {


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




        $this->title =

            is_array(
                $data['title']
                ??
                null
            )

            ?

            $data['title']

            :

            [];






        $this->sections =

            is_array(
                $data['sections']
                ??
                null
            )

            ?

            $data['sections']

            :

            [];







        $this->statistics =

            is_array(
                $data['statistics']
                ??
                null
            )

            ?

            $data['statistics']

            :

            [];







        $this->metadata =

            is_array(
                $data['metadata']
                ??
                null
            )

            ?

            $data['metadata']

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







    public function getTitle(): array
    {
        return $this->title;
    }







    public function getSections(): array
    {
        return $this->sections;
    }







    public function getStatistics(): array
    {
        return $this->statistics;
    }







    public function getMetadata(): array
    {
        return $this->metadata;
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
    | Structure Helpers
    |--------------------------------------------------------------------------
    */







    public function getSectionCount(): int
    {

        return count(
            $this->sections
        );

    }









    public function getImportantSections(): array
    {

        return array_values(

            array_filter(

                $this->sections,

                fn(array $section) =>

                in_array(

                    $section['importance']
                    ??
                    '',

                    [

                        'high',

                        'very_high'

                    ],

                    true

                )

            )

        );

    }









    public function findSection(
        string $name
    ): ?array {


        foreach (
            $this->sections as $section
        ) {

            if (

                strtolower(
                    $section['name']
                    ??
                    ''
                )

                ===

                strtolower(
                    $name
                )

            ) {

                return $section;

            }

        }



        return null;


    }









    public function isAcademicPaper(): bool
    {

        return

            (
                $this->metadata['type']
                ??
                null
            )

            ===

            'academic_paper';

    }





}
