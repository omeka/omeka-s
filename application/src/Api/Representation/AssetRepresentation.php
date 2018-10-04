<?php
namespace Omeka\Api\Representation;

class AssetRepresentation extends AbstractEntityRepresentation
{
    public function getControllerName()
    {
        return 'asset';
    }

    public function getJsonLdType()
    {
        return 'o:Asset';
    }

    public function getJsonLd()
    {
        $owner = null;
        if ($this->owner()) {
            $owner = $this->owner()->getReference();
        }
        return [
            'o:id' => $this->id(),
            'o:owner' => $owner,
            'o:name' => $this->name(),
            'o:filename' => $this->filename(),
            'o:media_type' => $this->mediaType(),
            'o:asset_url' => $this->assetUrl(),
        ];
    }

    public function owner()
    {
        return $this->getAdapter('users')->getRepresentation($this->resource->getOwner());
    }

    public function name()
    {
        return $this->resource->getName();
    }

    public function filename()
    {
        return $this->resource->getFilename();
    }

    public function mediaType()
    {
        return $this->resource->getMediaType();
    }

    public function assetUrl()
    {
        return $this->getFileUrl('asset', $this->filename());
    }
}
