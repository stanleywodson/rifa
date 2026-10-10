<?php

namespace App\Domain\Tenants\Models;

use App\Domain\Tenants\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class KycDocument extends Model
{
  use BelongsToTenant;

  public const TYPES = [
    'identity' => 'Documento de identidade (RG/CNH)',
    'selfie' => 'Selfie com documento',
    'company' => 'Contrato social / Estatuto',
    'address' => 'Comprovante de endereço',
  ];

  protected $fillable = ['tenant_id', 'uploaded_by', 'type', 'path', 'original_name'];

  public function typeLabel(): string
  {
    return self::TYPES[$this->type] ?? $this->type;
  }
}
