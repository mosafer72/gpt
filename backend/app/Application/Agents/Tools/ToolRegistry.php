<?php

namespace App\Application\Agents\Tools;

use App\Application\Agents\Contracts\AgentTool;
use RuntimeException;

class ToolRegistry
{
    /**
     * @var array<string, AgentTool>
     */
    protected array $tools = [];

    public function register(AgentTool $tool): self
    {
        $name = $tool->name();

        if (isset($this->tools[$name])) {
            throw new RuntimeException(
                "Agent tool [{$name}] is already registered."
            );
        }

        $this->tools[$name] = $tool;

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function get(string $name): AgentTool
    {
        if (!$this->has($name)) {
            throw new RuntimeException(
                "Agent tool [{$name}] does not exist."
            );
        }

        return $this->tools[$name];
    }

    /**
     * @return array<string, AgentTool>
     */
    public function all(): array
    {
        return $this->tools;
    }

    public function schemas(): array
    {
        return array_map(
            fn (AgentTool $tool) => [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'schema' => $tool->schema(),
            ],
            array_values($this->tools)
        );
    }
}
