# moveSomeAnswers

When using randomize order in answers, you can want to get some specific answers at last position, this plugin offer a solution.

This version is compatible with LimeSurvey 3.X and 5.X version. 
You can test on LimeSurvey 4.X verison without any warranty.

## Documentation

Before activate this plugin you need to get and activate [toolsSmartDomDocument plugin](https://gitlab.com/SondagesPro/coreAndTools/toolsDomDocument).

After you can set specific code to be always at end (if you use random_order attribute in a question).  The list of answer code is put at end if exist. The answer code can be separate by comma (,).

You have 3 settings:
- _Default globally_: managed at plugin settings ; set it to empty or dot (.) to deactivate by default
- _Default by survey_: each survey can have own default: set it to dot (.) to deactivate by survey ; to use global default use an empty string
- _New question attribute_: if question have random_order attribute, the new attribute is tested (get default from survey if is empty or not set).

## Copyright
- Copyright © 2016-2021 Denis Chenu <http://sondages.pro>
- Licence : GNU General Public License <https://www.gnu.org/licenses/gpl-3.0.html>
