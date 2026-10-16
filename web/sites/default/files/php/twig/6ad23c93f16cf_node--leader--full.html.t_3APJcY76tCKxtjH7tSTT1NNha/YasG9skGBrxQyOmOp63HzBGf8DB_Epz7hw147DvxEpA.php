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

/* themes/custom/dynetx/templates/node--leader--full.html.twig */
class __TwigTemplate_d65aafd05b6d43d7d9ef8b8f85421f78 extends Template
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
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", ["dyne-leader-detail", "wrapper", "pages"], "method", false, false, true, 2), "addClass", [((array_key_exists("dyne_page_classes", $context)) ? (Twig\Extension\CoreExtension::default(($context["dyne_page_classes"] ?? null), [])) : ([]))], "method", false, false, true, 2), "html", null, true);
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
    <div class=\"row g-4\">
      ";
        // line 7
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_native_details"] ?? null), "photo", [], "any", false, false, true, 7), "url", [], "any", false, false, true, 7)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "<div class=\"col-12 col-md-4 dyne-leader-detail__image\"><img src=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_native_details"] ?? null), "photo", [], "any", false, false, true, 7), "url", [], "any", false, false, true, 7), "html", null, true);
            yield "\" alt=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_native_details"] ?? null), "photo", [], "any", false, false, true, 7), "alt", [], "any", false, false, true, 7), "html", null, true);
            yield "\" class=\"img-fluid\" loading=\"lazy\"></div>";
        }
        // line 8
        yield "      <div class=\"col-12 col-md\">
        ";
        // line 9
        if ((($tmp = ($context["dyne_leader_role"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "<p><strong>";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dyne_leader_role"] ?? null), "html", null, true);
            yield "</strong></p>";
        }
        // line 10
        yield "        ";
        if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_native_details"] ?? null), "position_info", [], "any", false, false, true, 10)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            yield "<p>";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_native_details"] ?? null), "position_info", [], "any", false, false, true, 10), "html", null, true);
            yield "</p>";
        }
        // line 11
        yield "        <div class=\"dyne-legacy-content\">";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["content"] ?? null), "field_body_text", [], "any", false, false, true, 11), "html", null, true);
        yield "</div>
        ";
        // line 12
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["content"] ?? null), "field_link", [], "any", false, false, true, 12), "html", null, true);
        yield "
        <p class=\"mt-4\"><a href=\"";
        // line 13
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>"));
        yield "about-us/#leadership\">";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Meet our team"));
        yield "</a></p>
      </div>
    </div>
  </div>
</article>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["dyne_metadata_cache", "attributes", "dyne_page_classes", "title_prefix", "label", "title_suffix", "dyne_native_details", "dyne_leader_role", "content"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/custom/dynetx/templates/node--leader--full.html.twig";
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
        return array (  96 => 13,  92 => 12,  87 => 11,  80 => 10,  74 => 9,  71 => 8,  63 => 7,  54 => 5,  48 => 2,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/custom/dynetx/templates/node--leader--full.html.twig", "/var/www/html/web/themes/custom/dynetx/templates/node--leader--full.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 7];
        static $filters = ["escape" => 1, "default" => 2, "t" => 13];
        static $functions = ["path" => 13];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "if"],
                [0 => "escape", 1 => "default", 2 => "t"],
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
