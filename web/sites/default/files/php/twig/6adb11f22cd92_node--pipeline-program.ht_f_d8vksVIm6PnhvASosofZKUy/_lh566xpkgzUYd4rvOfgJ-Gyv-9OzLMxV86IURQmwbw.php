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

/* themes/custom/dynetx/templates/node--pipeline-program.html.twig */
class __TwigTemplate_b1d825264b86f47e76e9ea6ca1663fe4 extends Template
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
        $macros["pipeline"] = $this->macros["pipeline"] = $this->load("@dynetx/paragraph--dyne-program-pipeline.html.twig", 1)->unwrap();
        // line 2
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dyne_program_cache"] ?? null), "html", null, true);
        yield "
";
        // line 3
        if ((($tmp = ($context["dyne_program"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 4
            yield "  <article";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", ["dyne-program-detail", "wrapper", "pages"], "method", false, false, true, 4), "html", null, true);
            yield ">
    <div class=\"hero hero-third-level\" aria-hidden=\"true\"></div>
    <div class=\"container py-5\">
      ";
            // line 7
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_prefix"] ?? null), "html", null, true);
            yield "<h1>";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "title", [], "any", false, false, true, 7), "html", null, true);
            yield "</h1>";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["title_suffix"] ?? null), "html", null, true);
            yield "
      ";
            // line 8
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "image", [], "any", false, false, true, 8)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<img class=\"img-fluid mb-4\" src=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "image", [], "any", false, false, true, 8), "url", [], "any", false, false, true, 8), "html", null, true);
                yield "\" alt=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "image", [], "any", false, false, true, 8), "alt", [], "any", false, false, true, 8), "html", null, true);
                yield "\">";
            }
            // line 9
            yield "      <dl class=\"row\">
        ";
            // line 10
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "code", [], "any", false, false, true, 10)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<dt class=\"col-sm-3\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Program"));
                yield "</dt><dd class=\"col-sm-9\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "code", [], "any", false, false, true, 10), "html", null, true);
                yield "</dd>";
            }
            // line 11
            yield "        ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "short_name", [], "any", false, false, true, 11)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<dt class=\"col-sm-3\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Short name"));
                yield "</dt><dd class=\"col-sm-9\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "short_name", [], "any", false, false, true, 11), "html", null, true);
                yield "</dd>";
            }
            // line 12
            yield "        ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "aliases", [], "any", false, false, true, 12)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<dt class=\"col-sm-3\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Also known as"));
                yield "</dt><dd class=\"col-sm-9\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::join(CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "aliases", [], "any", false, false, true, 12), ", "), "html", null, true);
                yield "</dd>";
            }
            // line 13
            yield "        ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "disease", [], "any", false, false, true, 13)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<dt class=\"col-sm-3\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Disease"));
                yield "</dt><dd class=\"col-sm-9\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "disease", [], "any", false, false, true, 13), "html", null, true);
                yield "</dd>";
            }
            // line 14
            yield "        ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "prevalence_us", [], "any", false, false, true, 14)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<dt class=\"col-sm-3\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("U.S. prevalence"));
                yield "</dt><dd class=\"col-sm-9\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "prevalence_us", [], "any", false, false, true, 14), "html", null, true);
                yield "</dd>";
            }
            // line 15
            yield "        ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "prevalence_europe", [], "any", false, false, true, 15)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<dt class=\"col-sm-3\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Europe prevalence"));
                yield "</dt><dd class=\"col-sm-9\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "prevalence_europe", [], "any", false, false, true, 15), "html", null, true);
                yield "</dd>";
            }
            // line 16
            yield "        ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "target", [], "any", false, false, true, 16)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<dt class=\"col-sm-3\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Target"));
                yield "</dt><dd class=\"col-sm-9\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "target", [], "any", false, false, true, 16), "html", null, true);
                yield "</dd>";
            }
            // line 17
            yield "        ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "phase", [], "any", false, false, true, 17)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<dt class=\"col-sm-3\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Phase"));
                yield "</dt><dd class=\"col-sm-9\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "phase", [], "any", false, false, true, 17), "html", null, true);
                yield "</dd>";
            }
            // line 18
            yield "        ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "status", [], "any", false, false, true, 18)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<dt class=\"col-sm-3\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Milestone"));
                yield "</dt><dd class=\"col-sm-9\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["dyne_program"] ?? null), "status", [], "any", false, false, true, 18), "html", null, true);
                yield "</dd>";
            }
            // line 19
            yield "      </dl>
    </div>
    <div class=\"container-fluid section pipeline-description\">";
            // line 21
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar($macros["pipeline"]->getTemplateForMacro("macro_details", $context, 21, $this->getSourceContext())->macro_details(...[($context["dyne_program"] ?? null), false, "/pipeline#chart"]));
            yield "</div>
  </article>
";
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["dyne_program_cache", "dyne_program", "attributes", "title_prefix", "title_suffix"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/custom/dynetx/templates/node--pipeline-program.html.twig";
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
        return array (  162 => 21,  158 => 19,  149 => 18,  140 => 17,  131 => 16,  122 => 15,  113 => 14,  104 => 13,  95 => 12,  86 => 11,  78 => 10,  75 => 9,  67 => 8,  59 => 7,  52 => 4,  50 => 3,  46 => 2,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/custom/dynetx/templates/node--pipeline-program.html.twig", "/var/www/html/web/themes/custom/dynetx/templates/node--pipeline-program.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["import" => 1, "if" => 3];
        static $filters = ["escape" => 2, "t" => 10, "join" => 12];
        static $functions = [];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "import", 1 => "if"],
                [0 => "escape", 1 => "t", 2 => "join"],
                [],
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
