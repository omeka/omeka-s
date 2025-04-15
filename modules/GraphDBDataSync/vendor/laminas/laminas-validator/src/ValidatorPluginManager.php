<?php

declare(strict_types=1);

namespace Laminas\Validator;

use Closure;
use Laminas\ServiceManager\AbstractSingleInstancePluginManager;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Translator\TranslatorInterface;
use Psr\Container\ContainerInterface;

use function array_replace_recursive;
use function assert;

/**
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 * @extends AbstractSingleInstancePluginManager<ValidatorInterface>
 */
final class ValidatorPluginManager extends AbstractSingleInstancePluginManager
{
    private const DEFAULT_CONFIGURATION = [
        'factories' => [
            BackedEnumValue::class           => InvokableFactory::class,
            Barcode::class                   => InvokableFactory::class,
            Bitwise::class                   => InvokableFactory::class,
            BusinessIdentifierCode::class    => InvokableFactory::class,
            Callback::class                  => InvokableFactory::class,
            Conditional::class               => ConditionalFactory::class,
            CreditCard::class                => InvokableFactory::class,
            DateStep::class                  => InvokableFactory::class,
            Date::class                      => InvokableFactory::class,
            DateComparison::class            => InvokableFactory::class,
            DateIntervalString::class        => InvokableFactory::class,
            Digits::class                    => InvokableFactory::class,
            EmailAddress::class              => InvokableFactory::class,
            EnumCase::class                  => InvokableFactory::class,
            Explode::class                   => InvokableFactory::class,
            File\Count::class                => InvokableFactory::class,
            File\ExcludeExtension::class     => InvokableFactory::class,
            File\ExcludeMimeType::class      => InvokableFactory::class,
            File\Exists::class               => InvokableFactory::class,
            File\Extension::class            => InvokableFactory::class,
            File\FilesSize::class            => InvokableFactory::class,
            File\Hash::class                 => InvokableFactory::class,
            File\ImageSize::class            => InvokableFactory::class,
            File\IsCompressed::class         => InvokableFactory::class,
            File\IsImage::class              => InvokableFactory::class,
            File\MimeType::class             => InvokableFactory::class,
            File\NotExists::class            => InvokableFactory::class,
            File\Size::class                 => InvokableFactory::class,
            File\UploadFile::class           => InvokableFactory::class,
            File\WordCount::class            => InvokableFactory::class,
            GpsPoint::class                  => InvokableFactory::class,
            Hex::class                       => InvokableFactory::class,
            Hostname::class                  => InvokableFactory::class,
            HostWithPublicIPv4Address::class => InvokableFactory::class,
            Iban::class                      => InvokableFactory::class,
            Identical::class                 => InvokableFactory::class,
            InArray::class                   => InvokableFactory::class,
            Ip::class                        => InvokableFactory::class,
            IsArray::class                   => InvokableFactory::class,
            Isbn::class                      => InvokableFactory::class,
            IsCountable::class               => InvokableFactory::class,
            IsInstanceOf::class              => InvokableFactory::class,
            IsJsonString::class              => InvokableFactory::class,
            NotEmpty::class                  => InvokableFactory::class,
            NumberComparison::class          => InvokableFactory::class,
            Regex::class                     => InvokableFactory::class,
            Sitemap\Changefreq::class        => InvokableFactory::class,
            Sitemap\Lastmod::class           => InvokableFactory::class,
            Sitemap\Loc::class               => InvokableFactory::class,
            Sitemap\Priority::class          => InvokableFactory::class,
            StringLength::class              => InvokableFactory::class,
            Step::class                      => InvokableFactory::class,
            Timezone::class                  => InvokableFactory::class,
            Uri::class                       => InvokableFactory::class,
            Uuid::class                      => InvokableFactory::class,
        ],
        'aliases'   => [
            'barcode'                => Barcode::class,
            'Barcode'                => Barcode::class,
            'BIC'                    => BusinessIdentifierCode::class,
            'bic'                    => BusinessIdentifierCode::class,
            'bitwise'                => Bitwise::class,
            'Bitwise'                => Bitwise::class,
            'BusinessIdentifierCode' => BusinessIdentifierCode::class,
            'businessidentifiercode' => BusinessIdentifierCode::class,
            'callback'               => Callback::class,
            'Callback'               => Callback::class,
            'creditcard'             => CreditCard::class,
            'creditCard'             => CreditCard::class,
            'CreditCard'             => CreditCard::class,
            'date'                   => Date::class,
            'Date'                   => Date::class,
            'datestep'               => DateStep::class,
            'dateStep'               => DateStep::class,
            'DateStep'               => DateStep::class,
            'digits'                 => Digits::class,
            'Digits'                 => Digits::class,
            'emailaddress'           => EmailAddress::class,
            'emailAddress'           => EmailAddress::class,
            'EmailAddress'           => EmailAddress::class,
            'explode'                => Explode::class,
            'Explode'                => Explode::class,
            'filecount'              => File\Count::class,
            'fileCount'              => File\Count::class,
            'FileCount'              => File\Count::class,
            'fileexcludeextension'   => File\ExcludeExtension::class,
            'fileExcludeExtension'   => File\ExcludeExtension::class,
            'FileExcludeExtension'   => File\ExcludeExtension::class,
            'fileexcludemimetype'    => File\ExcludeMimeType::class,
            'fileExcludeMimeType'    => File\ExcludeMimeType::class,
            'FileExcludeMimeType'    => File\ExcludeMimeType::class,
            'fileexists'             => File\Exists::class,
            'fileExists'             => File\Exists::class,
            'FileExists'             => File\Exists::class,
            'fileextension'          => File\Extension::class,
            'fileExtension'          => File\Extension::class,
            'FileExtension'          => File\Extension::class,
            'filefilessize'          => File\FilesSize::class,
            'fileFilesSize'          => File\FilesSize::class,
            'FileFilesSize'          => File\FilesSize::class,
            'filehash'               => File\Hash::class,
            'fileHash'               => File\Hash::class,
            'FileHash'               => File\Hash::class,
            'fileimagesize'          => File\ImageSize::class,
            'fileImageSize'          => File\ImageSize::class,
            'FileImageSize'          => File\ImageSize::class,
            'fileiscompressed'       => File\IsCompressed::class,
            'fileIsCompressed'       => File\IsCompressed::class,
            'FileIsCompressed'       => File\IsCompressed::class,
            'fileisimage'            => File\IsImage::class,
            'fileIsImage'            => File\IsImage::class,
            'FileIsImage'            => File\IsImage::class,
            'filemimetype'           => File\MimeType::class,
            'fileMimeType'           => File\MimeType::class,
            'FileMimeType'           => File\MimeType::class,
            'filenotexists'          => File\NotExists::class,
            'fileNotExists'          => File\NotExists::class,
            'FileNotExists'          => File\NotExists::class,
            'filesize'               => File\Size::class,
            'fileSize'               => File\Size::class,
            'FileSize'               => File\Size::class,
            'fileuploadfile'         => File\UploadFile::class,
            'fileUploadFile'         => File\UploadFile::class,
            'FileUploadFile'         => File\UploadFile::class,
            'filewordcount'          => File\WordCount::class,
            'fileWordCount'          => File\WordCount::class,
            'FileWordCount'          => File\WordCount::class,
            'gpspoint'               => GpsPoint::class,
            'gpsPoint'               => GpsPoint::class,
            'GpsPoint'               => GpsPoint::class,
            'hex'                    => Hex::class,
            'Hex'                    => Hex::class,
            'hostname'               => Hostname::class,
            'Hostname'               => Hostname::class,
            'iban'                   => Iban::class,
            'Iban'                   => Iban::class,
            'identical'              => Identical::class,
            'Identical'              => Identical::class,
            'inarray'                => InArray::class,
            'inArray'                => InArray::class,
            'InArray'                => InArray::class,
            'ip'                     => Ip::class,
            'Ip'                     => Ip::class,
            'IsArray'                => IsArray::class,
            'isbn'                   => Isbn::class,
            'Isbn'                   => Isbn::class,
            'isCountable'            => IsCountable::class,
            'IsCountable'            => IsCountable::class,
            'iscountable'            => IsCountable::class,
            'isinstanceof'           => IsInstanceOf::class,
            'isInstanceOf'           => IsInstanceOf::class,
            'IsInstanceOf'           => IsInstanceOf::class,
            'notempty'               => NotEmpty::class,
            'notEmpty'               => NotEmpty::class,
            'NotEmpty'               => NotEmpty::class,
            'regex'                  => Regex::class,
            'Regex'                  => Regex::class,
            'sitemapchangefreq'      => Sitemap\Changefreq::class,
            'sitemapChangefreq'      => Sitemap\Changefreq::class,
            'SitemapChangefreq'      => Sitemap\Changefreq::class,
            'sitemaplastmod'         => Sitemap\Lastmod::class,
            'sitemapLastmod'         => Sitemap\Lastmod::class,
            'SitemapLastmod'         => Sitemap\Lastmod::class,
            'sitemaploc'             => Sitemap\Loc::class,
            'sitemapLoc'             => Sitemap\Loc::class,
            'SitemapLoc'             => Sitemap\Loc::class,
            'sitemappriority'        => Sitemap\Priority::class,
            'sitemapPriority'        => Sitemap\Priority::class,
            'SitemapPriority'        => Sitemap\Priority::class,
            'stringlength'           => StringLength::class,
            'stringLength'           => StringLength::class,
            'StringLength'           => StringLength::class,
            'step'                   => Step::class,
            'Step'                   => Step::class,
            'timezone'               => Timezone::class,
            'Timezone'               => Timezone::class,
            'uri'                    => Uri::class,
            'Uri'                    => Uri::class,
            'uuid'                   => Uuid::class,
            'Uuid'                   => Uuid::class,
        ],
    ];

    /**
     * Whether to share by default; default to false
     */
    protected bool $sharedByDefault = false;

    protected string $instanceOf = ValidatorInterface::class;

    /**
     * @param ServiceManagerConfiguration $config
     */
    public function __construct(ContainerInterface $creationContext, array $config = [])
    {
        /** @var ServiceManagerConfiguration $config */
        $config = array_replace_recursive(self::DEFAULT_CONFIGURATION, $config);
        parent::__construct($creationContext, $config);

        $this->addInitializer(Closure::fromCallable([$this, 'injectTranslator']));
        $this->addInitializer(Closure::fromCallable([$this, 'injectValidatorPluginManager']));
    }

    /** @internal */
    protected function injectTranslator(ContainerInterface $container, object $validator): void
    {
        if (! $validator instanceof Translator\TranslatorAwareInterface) {
            return;
        }

        if ($container->has('MvcTranslator')) {
            $translator = $container->get('MvcTranslator');
            assert($translator instanceof TranslatorInterface);
            $validator->setTranslator($translator);

            return;
        }

        if ($container->has(TranslatorInterface::class)) {
            $validator->setTranslator($container->get(TranslatorInterface::class));
        }
    }

    /** @internal */
    protected function injectValidatorPluginManager(
        ContainerInterface $container,
        object $validator,
    ): void {
        if (! $validator instanceof ValidatorPluginManagerAwareInterface) {
            return;
        }

        $validator->setValidatorPluginManager($this);
    }
}
