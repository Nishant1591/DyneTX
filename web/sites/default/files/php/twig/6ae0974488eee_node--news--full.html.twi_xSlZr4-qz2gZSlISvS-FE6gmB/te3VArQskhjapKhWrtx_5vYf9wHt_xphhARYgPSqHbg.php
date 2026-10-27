<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Sandbox\SecurityNotAllowedTestError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* themes/custom/dynetx/templates/node--news--full.html.twig */
class __TwigTemplate_82b93daf223f1f3a992dc1edc63494d8 extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 1
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dyne_metadata_cache"] ?? null), "html", null, true);
        yield "
<article";
        // line 2
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", ["dyne-news-detail", "wrapper", "pages"], "method", false, false, true, 2), "addClass", [((array_key_exists("dyne_page_classes", $context)) ? (Twig\Extension\CoreExtension::default(($context["dyne_page_classes"] ?? null), [])) : ([]))], "method", false, false, true, 2), "html", null, true);
        yield ">
  <div class=\"hero hero-third-level\" aria-hidden=\"true\"></div>
  <div class=\"container py-5\">
    ";
        // line 5
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_prefix"] ?? null), "html", null, true);
        yield "<h1>";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["label"] ?? null), "html", null, true);
        yield "</h1>";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_suffix"] ?? null), "html", null, true);
        yield "
    ";
        // line 6
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_native_details"] ?? null), "date", [], "any", false, false, true, 6)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "<p class=\"dyne-news-detail__date\"><time datetime=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_native_details"] ?? null), "date", [], "any", false, false, true, 6), "html", null, true);
            yield "Z\">";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->extensions['Twig\Extension\CoreExtension']->formatDate(CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_native_details"] ?? null), "date", [], "any", false, false, true, 6), "F j, Y", "UTC"), "html", null, true);
            yield "</time></p>";
        }
        // line 7
        yield "    ";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["content"] ?? null), "field_image", [], "any", false, false, true, 7), "html", null, true);
        yield "
    <div class=\"dyne-legacy-content\">";
        // line 8
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["content"] ?? null), "field_body_text", [], "any", false, false, true, 8), "html", null, true);
        yield "</div>
    ";
        // line 9
        if (((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_native_details"] ?? null), "url", [], "any", false, false, true, 9)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_native_details"] ?? null), "type", [], "any", false, false, true, 9) == "url"))) {
            // line 10
            yield "      <p><a href=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_native_details"] ?? null), "url", [], "any", false, false, true, 10), "html", null, true);
            yield "\" target=\"_blank\" rel=\"noopener noreferrer\">";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Read the original release"));
            yield "</a></p>
    ";
        } elseif (((($tmp = CoreExtension::getAttribute($this->env, $this->source,         // line 11
($context["dyne_native_details"] ?? null), "url", [], "any", false, false, true, 11)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_native_details"] ?? null), "type", [], "any", false, false, true, 11) == "file"))) {
            // line 12
            yield "      <p><a href=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_native_details"] ?? null), "url", [], "any", false, false, true, 12), "html", null, true);
            yield "\" download><span aria-hidden=\"true\">↓</span> ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Download publication"));
            yield "</a></p>
    ";
        }
        // line 14
        yield "    <p class=\"mt-4\"><a href=\"";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>"));
        yield "#latest-news\">";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Latest news"));
        yield "</a></p>
  </div>
</article>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["dyne_metadata_cache", "attributes", "dyne_page_classes", "title_prefix", "label", "title_suffix", "dyne_native_details", "content"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/custom/dynetx/templates/node--news--full.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  98 => 14,  90 => 12,  88 => 11,  81 => 10,  79 => 9,  75 => 8,  70 => 7,  62 => 6,  54 => 5,  48 => 2,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/custom/dynetx/templates/node--news--full.html.twig", "/var/www/html/web/themes/custom/dynetx/templates/node--news--full.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 6];
        static $filters = ["escape" => 1, "default" => 2, "date" => 6, "t" => 10];
        static $functions = ["path" => 14];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "if"],
                [0 => "escape", 1 => "default", 2 => "date", 3 => "t"],
                [0 => "path"],
                [],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            } elseif ($e instanceof SecurityNotAllowedTestError && isset($tests[$e->getTestName()])) {
                $e->setTemplateLine($tests[$e->getTestName()]);
            }

            throw $e;
        }

    }
}
