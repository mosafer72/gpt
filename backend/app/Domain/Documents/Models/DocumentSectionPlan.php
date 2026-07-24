<?php

namespace App\Domain\Documents\Models;


use App\Domain\Documents\Schemas\DocumentSectionPlanSchema;

use Surqlize\Attributes\Schema;
use Surqlize\Attributes\Table;
use Surqlize\Model\Model;



#[Table('document_section_plan')]
#[Schema(DocumentSectionPlanSchema::class)]
class DocumentSectionPlan extends Model
{


    private string $id = '';

    private string $documentId = '';

    private string $workspaceId = '';

    private string $section = '';

    private string $sectionTitle = '';

    private string $summary = '';

    private array $keywords = [];

    private array $questionTypes = [];

    private float $answerProbability = 0;

    private int $plannerRank = 0;

    private int $selectedCount = 0;

    private array $metadata = [];

    private ?string $createdAt = null;

    private ?string $updatedAt = null;





    public function __construct(
        array $data
    )
    {


        $this->id =
            (string)
            ($data['id'] ?? '');



        $this->documentId =
            (string)
            ($data['document_id'] ?? '');



        $this->workspaceId =
            (string)
            ($data['workspace_id'] ?? '');



        $this->section =
            (string)
            ($data['section'] ?? '');



        $this->sectionTitle =
            (string)
            ($data['section_title'] ?? '');



        $this->summary =
            (string)
            ($data['summary'] ?? '');



        $this->keywords =
            $data['keywords'] ?? [];



        $this->questionTypes =
            $data['question_types'] ?? [];



        $this->answerProbability =
            (float)
            ($data['answer_probability'] ?? 0);



        $this->plannerRank =
            (int)
            ($data['planner_rank'] ?? 0);



        $this->selectedCount =
            (int)
            ($data['selected_count'] ?? 0);



        $this->metadata =
            $data['metadata'] ?? [];



        $this->createdAt =
            $data['created_at'] ?? null;



        $this->updatedAt =
            $data['updated_at'] ?? null;


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



    public function getSectionTitle(): string
    {
        return $this->sectionTitle;
    }



    public function getSummary(): string
    {
        return $this->summary;
    }



    public function getKeywords(): array
    {
        return $this->keywords;
    }



    public function getQuestionTypes(): array
    {
        return $this->questionTypes;
    }



    public function getAnswerProbability(): float
    {
        return $this->answerProbability;
    }



    public function getPlannerRank(): int
    {
        return $this->plannerRank;
    }



    public function incrementSelected(): void
    {
        $this->selectedCount++;
    }



    public function isHighConfidence(): bool
    {

        return
            $this->answerProbability >= 0.75;

    }



}
