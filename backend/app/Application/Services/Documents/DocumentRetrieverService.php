<?php

namespace App\Application\Services\Documents;

use App\Domain\Contracts\DocumentRetriever;
use App\Infrastructure\Repositories\DocumentSectionPlanRepository;
use App\Infrastructure\Repositories\DocumentSectionRepository;
use App\Infrastructure\Repositories\DocumentChunkRepository;
use App\Infrastructure\Repositories\DocumentStructureRepository;
use Illuminate\Support\Facades\Log;

class DocumentRetrieverService implements DocumentRetriever
{
    public function __construct(
        protected DocumentStructureRepository $structureRepository,
        protected DocumentSectionPlanRepository $sectionPlanRepository,
        protected DocumentSectionRepository $sectionRepository,
        protected DocumentChunkRepository $chunkRepository,
        protected QueryIntentClassifier $classifier,
        protected SectionPlanner $planner,
        protected DocumentContextBuilder $contextBuilder
    ) {
    }


    public function retrieve(
        string $workspaceId,
        string $query,
        int $limit = 5,
        array $documentIds = []
    ): array {

        if (blank($query) || empty($documentIds)) {
            return $this->emptyResult();
        }


        $intent = $this->classifier->classify($query);


        if (($intent['type'] ?? null) === 'title') {

            return [
                ...$this->metadataResult($documentIds),
                'intent' => $intent
            ];
        }


        $section = strtolower(
            trim($intent['type'] ?? '')
        );


        $directSections = [
            'abstract',
            'introduction',
            'methodology',
            'method',
            'results',
            'conclusion',
            'discussion'
        ];


        if (in_array($section, $directSections, true)) {

            $chunks = $this->sectionSearch(
                $documentIds,
                $section
            );



            if (!empty($chunks)) {

                return [
                    'chunks' => $chunks,
                    'context' => [],
                    'text' => $this->buildText($chunks),
                    'intent' => $intent
                ];
            }
        }


        $sections = $this->loadSections($documentIds);


        $plan = $this->planner->plan(
            intent: $intent,
            sections: $sections,
            query: $query
        );


        return [
            'chunks' => [],
            'context' => [],
            'text' => '',
            'intent' => $intent
        ];
    }


    protected function sectionSearch(
        array $documentIds,
        string $section
    ): array {

        $chunks = [];


        foreach ($documentIds as $documentId) {

            $items = $this->chunkRepository
                ->findByDocumentAndSection(
                    (string)$documentId,
                    $section
                );


            foreach ($items as $item) {

                $content = is_array($item)
                    ? ($item['content'] ?? '')
                    : ($item->content ?? '');


                if (blank($content)) {
                    continue;
                }


                $metadata = is_array($item)
                    ? ($item['metadata'] ?? [])
                    : [];


                $chunks[] = [
                    'content' => $content,
                    'score' => 1,
                    'metadata' => [
                        'section' => $section,
                        'section_title' => $metadata['section_title'] ?? ucfirst($section),
                        'title' => $metadata['title'] ?? 'Document',
                        'chunk_index' => $item['chunk_index'] ?? null
                    ]
                ];
            }
        }


        return $chunks;
    }


    protected function loadSections(array $documentIds): array
    {
        $sections = [];


        foreach ($documentIds as $id) {

            $structure = $this->structureRepository
                ->findByDocument((string)$id);


            $sections = array_merge(
                $sections,
                $structure['sections'] ?? []
            );
        }


        return $sections;
    }


    protected function metadataResult(array $documentIds): array
    {
        $documents = [];


        foreach ($documentIds as $id) {

            $document = \App\Models\Document::find($id);


            if (!$document) {
                continue;
            }


            $documents[] = [
                'content' => '',
                'metadata' => [
                    'title' => pathinfo(
                        $document->name,
                        PATHINFO_FILENAME
                    ),
                    'filename' => $document->name
                ]
            ];
        }


        return [
            'chunks' => $documents,
            'context' => [],
            'text' => ''
        ];
    }


    protected function buildText(array $chunks): string
    {
        return collect($chunks)
            ->pluck('content')
            ->filter()
            ->implode("\n\n");
    }


    protected function emptyResult(): array
    {
        return [
            'chunks' => [],
            'context' => [],
            'text' => ''
        ];
    }
}
