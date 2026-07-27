<?php

namespace App\Domain\Documents;


class DocumentStructure
{

    public function __construct(

        public readonly DocumentMetadata $metadata,

        public readonly array $sections = [],

        public readonly array $figures = [],

        public readonly array $tables = [],

        public readonly ?string $references = null,

    ) {
    }




    public function findSection(
        string $title
    ): ?DocumentSection {


        foreach ($this->sections as $section) {


            if (
                strtolower($section->title)
                ===
                strtolower($title)
            ) {

                return $section;

            }

        }


        return null;

    }






    public function sectionTitles(): array
    {

        return collect($this->sections)

            ->map(
                fn(DocumentSection $section)=>
                    $section->title
            )

            ->values()

            ->all();

    }






    public function toArray(): array
    {

        return [

            'metadata' =>
                $this->metadata->toArray(),


            'sections' =>
                collect($this->sections)

                ->map(
                    fn($section)=>
                    $section->toArray()
                )

                ->all(),



            'figures'=>
                collect($this->figures)

                ->map(
                    fn($figure)=>
                    $figure->toArray()
                )

                ->all(),



            'tables'=>
                collect($this->tables)

                ->map(
                    fn($table)=>
                    $table->toArray()
                )

                ->all(),



            'references'=>
                $this->references,

        ];

    }

}
