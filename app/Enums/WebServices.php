<?php

namespace App\Enums;

enum WebServices: string
{
    case SUTRAN      = "Sutran";
    case OSINERGMIN   = "Osinergmin";

    public function labels(): string
    {
        return match ($this) {
            WebServices::SUTRAN         => "🚛 Sutran",
            WebServices::OSINERGMIN       => "🏭 Osinergmin",
        };
    }

    public function labelPowergridFilter(): string
    {
        return $this->labels();
    }
}
