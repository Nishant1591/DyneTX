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

/* themes/custom/dynetx/templates/paragraph--dyne-jobs-list.html.twig */
class __TwigTemplate_13059095a66207da462686c691ae2a45 extends Template
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
        // line 2
        yield "<div";
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["attributes"] ?? null), "addClass", ["dyne-jobs"], "method", false, false, true, 2), "setAttribute", ["id", ((CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["paragraph"] ?? null), "field_anchor", [], "any", false, true, true, 2), "value", [], "any", true, true, true, 2)) ? (Twig\Extension\CoreExtension::default(CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["paragraph"] ?? null), "field_anchor", [], "any", false, false, true, 2), "value", [], "any", false, false, true, 2), "")) : (""))], "method", false, false, true, 2), "setAttribute", ["data-dyne-jobs-source", (((($tmp = ($context["dyne_use_job_records"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) ? ("content") : ("paragraph"))], "method", false, false, true, 2), "html", null, true);
        yield ">
  ";
        // line 3
        yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["dyne_job_cache"] ?? null), "html", null, true);
        yield "
  ";
        // line 4
        if ((((($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["paragraph"] ?? null), "field_heading", [], "any", false, false, true, 4), "value", [], "any", false, false, true, 4)) && $tmp instanceof Markup ? (string) $tmp : $tmp) &&  !(($tmp = ($context["dyne_legacy"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) || (($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["paragraph"] ?? null), "field_body_text", [], "any", false, false, true, 4), "value", [], "any", false, false, true, 4)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
            // line 5
            yield "    <div class=\"container\">
      ";
            // line 6
            if (((($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["paragraph"] ?? null), "field_heading", [], "any", false, false, true, 6), "value", [], "any", false, false, true, 6)) && $tmp instanceof Markup ? (string) $tmp : $tmp) &&  !(($tmp = ($context["dyne_legacy"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp))) {
                yield "<h2 class=\"h1\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["paragraph"] ?? null), "field_heading", [], "any", false, false, true, 6), "value", [], "any", false, false, true, 6), "html", null, true);
                yield "</h2>";
            }
            // line 7
            yield "      ";
            if ((($tmp = CoreExtension::getAttribute($this->env, $this->source, CoreExtension::getAttribute($this->env, $this->source, ($context["paragraph"] ?? null), "field_body_text", [], "any", false, false, true, 7), "value", [], "any", false, false, true, 7)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<div class=\"dyne-jobs__intro\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["content"] ?? null), "field_body_text", [], "any", false, false, true, 7), "html", null, true);
                yield "</div>";
            }
            // line 8
            yield "    </div>
  ";
        }
        // line 10
        yield "  ";
        if ((($tmp = ($context["dyne_use_job_records"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 11
            yield "    <div class=\"container-fluid section positions_filter\">
      <div class=\"container\"><div class=\"row\">
        <div class=\"col-6\">
          <label for=\"dyne-department-";
            // line 14
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["paragraph"] ?? null), "id", [], "method", false, false, true, 14), "html", null, true);
            yield "\">";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Department"));
            yield "</label>
          <select id=\"dyne-department-";
            // line 15
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["paragraph"] ?? null), "id", [], "method", false, false, true, 15), "html", null, true);
            yield "\" data-dyne-job-filter=\"department\">
            <option value=\"\">";
            // line 16
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("ALL"));
            yield "</option>
            ";
            // line 17
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["dyne_job_departments"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["department"]) {
                yield "<option value=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["department"], "filter_key", [], "any", false, false, true, 17), "html", null, true);
                yield "\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["department"], "label", [], "any", false, false, true, 17), "html", null, true);
                yield "</option>";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['department'], $context['_parent']);
            $context = array_intersect_key($context, $_parent);
            $context += $_parent;
            // line 18
            yield "          </select>
        </div>
        <div class=\"col-6\">
          <label for=\"dyne-location-";
            // line 21
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["paragraph"] ?? null), "id", [], "method", false, false, true, 21), "html", null, true);
            yield "\">";
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Location"));
            yield "</label>
          <select id=\"dyne-location-";
            // line 22
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, ($context["paragraph"] ?? null), "id", [], "method", false, false, true, 22), "html", null, true);
            yield "\" data-dyne-job-filter=\"location\">
            <option value=\"\">";
            // line 23
            yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("ALL"));
            yield "</option>
            ";
            // line 24
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["dyne_job_locations"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["location"]) {
                yield "<option value=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["location"], "filter_key", [], "any", false, false, true, 24), "html", null, true);
                yield "\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["location"], "label", [], "any", false, false, true, 24), "html", null, true);
                yield "</option>";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['location'], $context['_parent']);
            $context = array_intersect_key($context, $_parent);
            $context += $_parent;
            // line 25
            yield "          </select>
        </div>
      </div></div>
    </div>
    <div class=\"container-fluid section positions\">
      <div class=\"container\"><div class=\"row\"><div class=\"col-12\">
        <div class=\"accordion md-accordion\">
          ";
            // line 32
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["dyne_job_groups"] ?? null));
            $context['_iterated'] = false;
            foreach ($context['_seq'] as $context["_key"] => $context["group"]) {
                // line 33
                yield "            <div class=\"dyne-jobs-group\" data-dyne-job-group=\"";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["group"], "filter_key", [], "any", false, false, true, 33), "html", null, true);
                yield "\">
              <h3 class=\"category\">";
                // line 34
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::upper($this->env->getCharset(), CoreExtension::getAttribute($this->env, $this->source, $context["group"], "label", [], "any", false, false, true, 34)), "html", null, true);
                yield "</h3>
              ";
                // line 35
                $context['_parent'] = $context;
                $context['_seq'] = CoreExtension::ensureTraversable(CoreExtension::getAttribute($this->env, $this->source, $context["group"], "jobs", [], "any", false, false, true, 35));
                foreach ($context['_seq'] as $context["_key"] => $context["job"]) {
                    // line 36
                    yield "                <div class=\"card\" data-dyne-job-location=\"";
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["job"], "location_filter", [], "any", false, false, true, 36), "html", null, true);
                    yield "\">
                  <div class=\"card-header\">
                    <a class=\"collapsed\" href=\"";
                    // line 38
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["job"], "url", [], "any", false, false, true, 38), "html", null, true);
                    yield "\">
                      <h5 class=\"mb-0\">";
                    // line 39
                    yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["job"], "title", [], "any", false, false, true, 39), "html", null, true);
                    yield "</h5>
                      <span class=\"btn btn-primary btn-plus\" aria-hidden=\"true\"><i></i></span>
                    </a>
                  </div>
                </div>
              ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_key'], $context['job'], $context['_parent']);
                $context = array_intersect_key($context, $_parent);
                $context += $_parent;
                // line 45
                yield "            </div>
          ";
                $context['_iterated'] = true;
            }
            // line 46
            if (!$context['_iterated']) {
                // line 47
                yield "            <p>";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("There are no open positions at this time."));
                yield "</p>
          ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['group'], $context['_parent'], $context['_iterated']);
            $context = array_intersect_key($context, $_parent);
            $context += $_parent;
            // line 49
            yield "        </div>
        ";
            // line 50
            if ((($tmp = ($context["dyne_job_groups"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
                yield "<p data-dyne-jobs-empty hidden>";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("No open positions match these filters."));
                yield "</p>";
            }
            // line 51
            yield "      </div></div></div>
    </div>
  ";
        } else {
            // line 54
            yield "    <div class=\"container\"><ul class=\"list-group\">
      ";
            // line 55
            $context['_parent'] = $context;
            $context['_seq'] = CoreExtension::ensureTraversable(($context["dyne_items"] ?? null));
            foreach ($context['_seq'] as $context["_key"] => $context["job"]) {
                // line 56
                yield "        <li class=\"list-group-item d-flex justify-content-between align-items-center\">
          <span><a href=\"";
                // line 57
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["job"], "url", [], "any", false, false, true, 57), "html", null, true);
                yield "\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["job"], "title", [], "any", false, false, true, 57), "html", null, true);
                yield "</a><br /><small>";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["job"], "department", [], "any", false, false, true, 57), "html", null, true);
                yield " · ";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["job"], "location", [], "any", false, false, true, 57), "html", null, true);
                yield "</small></span>
          <a href=\"";
                // line 58
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, CoreExtension::getAttribute($this->env, $this->source, $context["job"], "url", [], "any", false, false, true, 58), "html", null, true);
                yield "\" class=\"btn btn-primary\">";
                yield (string) $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Apply"));
                yield "</a>
        </li>
      ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_key'], $context['job'], $context['_parent']);
            $context = array_intersect_key($context, $_parent);
            $context += $_parent;
            // line 61
            yield "    </ul></div>
  ";
        }
        // line 63
        yield "</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["attributes", "paragraph", "dyne_use_job_records", "dyne_job_cache", "dyne_legacy", "content", "dyne_job_departments", "dyne_job_locations", "dyne_job_groups", "dyne_items"]);        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "themes/custom/dynetx/templates/paragraph--dyne-jobs-list.html.twig";
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
        return array (  261 => 63,  257 => 61,  245 => 58,  235 => 57,  232 => 56,  228 => 55,  225 => 54,  220 => 51,  214 => 50,  211 => 49,  201 => 47,  199 => 46,  194 => 45,  181 => 39,  177 => 38,  171 => 36,  167 => 35,  163 => 34,  158 => 33,  153 => 32,  144 => 25,  130 => 24,  126 => 23,  122 => 22,  116 => 21,  111 => 18,  97 => 17,  93 => 16,  89 => 15,  83 => 14,  78 => 11,  75 => 10,  71 => 8,  64 => 7,  58 => 6,  55 => 5,  53 => 4,  49 => 3,  44 => 2,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "themes/custom/dynetx/templates/paragraph--dyne-jobs-list.html.twig", "/var/www/html/web/themes/custom/dynetx/templates/paragraph--dyne-jobs-list.html.twig");
    }
    
    public function ensureSecurityChecked(): void
    {
        if ($this->sandbox->isSandboxed($this->source)) {
            $this->checkSecurity();
        }
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 4, "for" => 17];
        static $filters = ["escape" => 2, "default" => 2, "t" => 14, "upper" => 34];
        static $functions = [];
        static $tests = [];

        try {
            $this->sandbox->checkSecurity(
                [0 => "if", 1 => "for"],
                [0 => "escape", 1 => "default", 2 => "t", 3 => "upper"],
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
