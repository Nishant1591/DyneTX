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

/* themes/custom/dynetx/templates/paragraph--dyne-news-list.html.twig */
class __TwigTemplate_893d7bfe5af7c84dff3285834e4f657e extends Template
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
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dyne_component_cache"] ?? null), "html", null, true);
        yield "
";
        // line 3
        if ((($tmp = ($context["dyne_is_home"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 4
            yield "  <section";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "setAttribute", ["id", "latest-news"], "method", false, false, true, 4), "addClass", ["container-fluid", "section", "section-news", "bg-deep-blue", "facelifting"], "method", false, false, true, 4), "html", null, true);
            yield ">
    <div class=\"bg-img bg-img-left\">
      <picture>
        <source srcset=\"";
            // line 7
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>") . ($context["directory"] ?? null)), "html", null, true);
            yield "/images/news/news-mobile@2x.webp\" media=\"(max-width: 767.98px)\" />
        <source srcset=\"";
            // line 8
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>") . ($context["directory"] ?? null)), "html", null, true);
            yield "/images/news/news-desktop@2x.webp\" media=\"(min-resolution: 1.5dppx)\" />
        <img src=\"";
            // line 9
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($this->extensions['Drupal\Core\Template\TwigExtension']->getPath("<front>") . ($context["directory"] ?? null)), "html", null, true);
            yield "/images/news/news-desktop.webp\" alt=\"";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Dyne Therapeutics office building"));
            yield "\" />
      </picture>
    </div>
    <div class=\"container\"><div class=\"row\">
      <div class=\"col-md-6 offset-md-6 col-lg-7 offset-lg-5 offset-xl-4 section-news-content\">
        <h2 class=\"h2 h2-new h2-new-light\"><span>";
            // line 14
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Latest"));
            yield "</span> <span class=\"h2-new-medium\">";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("news"));
            yield "</span></h2>
        ";
            // line 15
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["dyne_items"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["item"]) {
                // line 16
                yield "          <div class=\"section-news-item\">
            <p class=\"section-news-date\">";
                // line 17
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "date", [], "any", false, false, true, 17), "html", null, true);
                yield "</p>
            <a class=\"section-news-item-title\" href=\"";
                // line 18
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "url", [], "any", false, false, true, 18), "html", null, true);
                yield "\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "title", [], "any", false, false, true, 18), "html", null, true);
                yield "</a>
            <a class=\"btn btn-primary btn-lg\" href=\"";
                // line 19
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "url", [], "any", false, false, true, 19), "html", null, true);
                yield "\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Read more"));
                yield "</a>
          </div>
        ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['item'], $context['_parent']);
            $context = array_intersect_key($context, $_parent);
            $context += $_parent;
            // line 22
            yield "      </div>
    </div></div>
  </section>
";
        } else {
            // line 26
            yield "  <section";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", ["dyne-news-list", "container", "py-5"], "method", false, false, true, 26), "html", null, true);
            yield ">
    ";
            // line 27
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["paragraph"] ?? null), "field_heading", [], "any", false, false, true, 27), "value", [], "any", false, false, true, 27)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<h2 class=\"h1\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["paragraph"] ?? null), "field_heading", [], "any", false, false, true, 27), "value", [], "any", false, false, true, 27), "html", null, true);
                yield "</h2>";
            }
            // line 28
            yield "    ";
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["dyne_items"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["item"]) {
                // line 29
                yield "      <div class=\"col-12 card news-short\">
        <div class=\"row card-body align-items-center\">
          <h3 class=\"h4 col-12 col-md-3 card-title text-nowrap\">";
                // line 31
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "date", [], "any", false, false, true, 31), "html", null, true);
                yield "</h3>
          <p class=\"col card-text\"><a href=\"";
                // line 32
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "url", [], "any", false, false, true, 32), "html", null, true);
                yield "\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "title", [], "any", false, false, true, 32), "html", null, true);
                yield "</a></p>
          <div class=\"col-12 col-md-auto\"><a href=\"";
                // line 33
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["item"], "url", [], "any", false, false, true, 33), "html", null, true);
                yield "\" class=\"btn btn-primary stretched-link\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Read more"));
                yield "</a></div>
        </div>
      </div>
    ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['item'], $context['_parent']);
            $context = array_intersect_key($context, $_parent);
            $context += $_parent;
            // line 37
            yield "  </section>
";
        }
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["dyne_component_cache", "dyne_is_home", "attributes", "directory", "dyne_items", "paragraph"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/custom/dynetx/templates/paragraph--dyne-news-list.html.twig";
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
        return array (  159 => 37,  146 => 33,  140 => 32,  136 => 31,  132 => 29,  127 => 28,  121 => 27,  116 => 26,  110 => 22,  98 => 19,  92 => 18,  88 => 17,  85 => 16,  81 => 15,  75 => 14,  65 => 9,  61 => 8,  57 => 7,  50 => 4,  48 => 3,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/custom/dynetx/templates/paragraph--dyne-news-list.html.twig", "/var/www/html/web/themes/custom/dynetx/templates/paragraph--dyne-news-list.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 3, "for" => 15];
        static $filters = ["escape" => 1, "t" => 9];
        static $functions = ["path" => 7];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "if", 1 => "for"],
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
