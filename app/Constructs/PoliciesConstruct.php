<?php

namespace App\Constructs;

trait PoliciesConstruct
{
    protected function setupPolicies(): self
    {
        if (!$this->get('Policies')) {
            return $this;
        }

        foreach ($this->get('Resources') as $name => $resource) {
            if (!str_ends_with($name, 'Function')) {
                continue;
            }

            $this->append("Resources.$name.Properties.Policies", $this->get('Policies'));
        }

        return $this->forget('Policies');
    }
}
