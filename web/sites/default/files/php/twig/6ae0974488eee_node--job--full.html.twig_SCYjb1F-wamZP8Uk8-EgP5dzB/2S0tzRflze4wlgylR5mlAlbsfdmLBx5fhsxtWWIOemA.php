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

/* themes/custom/dynetx/templates/node--job--full.html.twig */
class __TwigTemplate_e3a7007a040d5623094908ceca207af4 extends Template
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
";
        // line 2
        if ((($tmp = ($context["dyne_legacy"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 3
            yield "  <div class=\"wrapper pages\">";
            yield from $this->load("@dynetx/includes/legacy-hero.html.twig", 3)->unwrap()->yield($context);
            yield "</div>
";
        } else {
            // line 5
            yield "<section class=\"dyne-job-hero\" aria-label=\"Careers and culture\">
  <picture class=\"dyne-job-hero__base\">
    <source media=\"(max-width: 767px)\" srcset=\"";
            // line 7
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>"));
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["directory"] ?? null), "html", null, true);
            yield "/images/hero-careers-bg-base-mobile@2x.webp\" />
    <img src=\"";
            // line 8
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>"));
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["directory"] ?? null), "html", null, true);
            yield "/images/hero-careers-bg-base-desktop@2x-1-scaled.webp\" alt=\"\" fetchpriority=\"high\" />
  </picture>
  <img class=\"dyne-job-hero__overlay\" src=\"";
            // line 10
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>"));
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["directory"] ?? null), "html", null, true);
            yield "/images/hero-careers-bg-overlay-desktop@2x-1-scaled.webp\" alt=\"\" />
  <p class=\"dyne-job-hero__word dyne-job-hero__word--more\" aria-hidden=\"true\">more</p>
  <p class=\"dyne-job-hero__word dyne-job-hero__word--passion\" aria-hidden=\"true\">passion</p>
</section>
";
        }
        // line 15
        yield "
<article";
        // line 16
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", ["dyne-job-detail"], "method", false, false, true, 16), "html", null, true);
        yield ">
  <h1 class=\"dyne-job-detail__title\">";
        // line 17
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["label"] ?? null), "html", null, true);
        yield "</h1>

  ";
        // line 19
        if ((($tmp = ($context["dyne_apply_url"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 20
            yield "    <div class=\"dyne-job-detail__apply dyne-job-detail__apply--top\">
      <a class=\"btn btn-primary btn-lg\" href=\"";
            // line 21
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dyne_apply_url"] ?? null), "html", null, true);
            yield "\" target=\"_blank\" rel=\"noopener\">";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Apply for this job"));
            yield "</a>
    </div>
  ";
        }
        // line 24
        yield "
  <div class=\"dyne-job-detail__meta\">
    ";
        // line 26
        if ((($tmp = ($context["dyne_job_department"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 27
            yield "      <p><strong>";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Department:"));
            yield "</strong> ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dyne_job_department"] ?? null), "html", null, true);
            yield "</p>
    ";
        }
        // line 29
        yield "    ";
        if ((($tmp = ($context["dyne_job_location"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 30
            yield "      <p><strong>";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Location:"));
            yield "</strong> ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dyne_job_location"] ?? null), "html", null, true);
            yield "</p>
    ";
        }
        // line 32
        yield "    ";
        if ((($tmp = ($context["dyne_job_workplace"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 33
            yield "      <p class=\"dyne-job-workplace\"><strong>";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Workplace arrangement:"));
            yield "</strong> ";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dyne_job_workplace"] ?? null), "html", null, true);
            yield "</p>
    ";
        }
        // line 35
        yield "  </div>

  <div class=\"dyne-job-detail__body\">
    ";
        // line 38
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["content"] ?? null), "field_body_text", [], "any", false, false, true, 38), "html", null, true);
        yield "
  </div>

  ";
        // line 41
        if ((($tmp = ($context["dyne_apply_url"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 42
            yield "    <div class=\"dyne-job-detail__apply dyne-job-detail__apply--bottom\">
      <a class=\"btn btn-primary btn-lg\" href=\"";
            // line 43
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dyne_apply_url"] ?? null), "html", null, true);
            yield "\" target=\"_blank\" rel=\"noopener\">";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Apply for this job"));
            yield "</a>
    </div>
  ";
        }
        // line 46
        yield "
  ";
        // line 47
        if (((($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["node"] ?? null), "field_previous_url", [], "any", false, false, true, 47), "value", [], "any", false, false, true, 47)) && $tmp instanceof Markup ? (string) $tmp : $tmp) || (($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["node"] ?? null), "field_next_url", [], "any", false, false, true, 47), "value", [], "any", false, false, true, 47)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
            // line 48
            yield "    <nav class=\"dyne-job-detail__pagination\" aria-label=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Job navigation"));
            yield "\">
      ";
            // line 49
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["node"] ?? null), "field_previous_url", [], "any", false, false, true, 49), "value", [], "any", false, false, true, 49)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 50
                yield "        <a href=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["node"] ?? null), "field_previous_url", [], "any", false, false, true, 50), "value", [], "any", false, false, true, 50), "html", null, true);
                yield "\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("BACK"));
                yield "</a>
      ";
            }
            // line 52
            yield "      ";
            if (((($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["node"] ?? null), "field_previous_url", [], "any", false, false, true, 52), "value", [], "any", false, false, true, 52)) && $tmp instanceof Markup ? (string) $tmp : $tmp) && (($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["node"] ?? null), "field_next_url", [], "any", false, false, true, 52), "value", [], "any", false, false, true, 52)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
                yield "<span aria-hidden=\"true\">|</span>";
            }
            // line 53
            yield "      ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["node"] ?? null), "field_next_url", [], "any", false, false, true, 53), "value", [], "any", false, false, true, 53)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                // line 54
                yield "        <a href=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["node"] ?? null), "field_next_url", [], "any", false, false, true, 54), "value", [], "any", false, false, true, 54), "html", null, true);
                yield "\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("NEXT"));
                yield "</a>
      ";
            }
            // line 56
            yield "    </nav>
  ";
        }
        // line 58
        yield "</article>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["dyne_metadata_cache", "dyne_legacy", "directory", "attributes", "label", "dyne_apply_url", "dyne_job_department", "dyne_job_location", "dyne_job_workplace", "content", "node"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/custom/dynetx/templates/node--job--full.html.twig";
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
        return array (  205 => 58,  201 => 56,  193 => 54,  190 => 53,  185 => 52,  177 => 50,  175 => 49,  170 => 48,  168 => 47,  165 => 46,  157 => 43,  154 => 42,  152 => 41,  146 => 38,  141 => 35,  133 => 33,  130 => 32,  122 => 30,  119 => 29,  111 => 27,  109 => 26,  105 => 24,  97 => 21,  94 => 20,  92 => 19,  87 => 17,  83 => 16,  80 => 15,  71 => 10,  65 => 8,  60 => 7,  56 => 5,  50 => 3,  48 => 2,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/custom/dynetx/templates/node--job--full.html.twig", "/var/www/html/web/themes/custom/dynetx/templates/node--job--full.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 2, "include" => 3];
        static $filters = ["escape" => 1, "t" => 21];
        static $functions = ["path" => 7];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "if", 1 => "include"],
                [0 => "escape", 1 => "t"],
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
