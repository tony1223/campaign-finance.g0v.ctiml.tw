<?php

class ApiController extends Pix_Controller
{
    public function fillcellAction()
    {
        list(, /*api*/, /*fillcell*/, $page, $x, $y) = explode('/', $this->getURI());
        $page = intval($page);
        $x = intval($x);
        $y = intval($y);
        $ans = $_POST['ans'];
        
        $values = array('page' => $page, 'x' => $x, 'y' => $y);
        $cell = Cell::search($values)->first();
        CellHistory::insert(array_merge($values, array(
            'ans' => $ans,
            'client_ip' => $_SERVER["REMOTE_ADDR"],
            'created' => time()
        )));
        if ($cell == NULL) {
            Cell::insert(array_merge($values, array('ans' => $ans)));
        } else {
            $cell->ans = $ans;
            $cell->save();
            echo $cell->page . "/" . $cell->x . "/" . $cell->y . " => " . $cell->ans;
        }
        return $this->noview();
    }

    public function getcellvalueAction()
    {
        list(, /*api*/, /*getcellvalue*/, $page, $x, $y) = explode('/', $this->getURI());
        $page = intval($page);
        $x = intval($x);
        $y = intval($y);

        $values = array('page' => $page, 'x' => $x, 'y' => $y);
        $cell = Cell::search($values)->first();
        if ($cell == NULL) {
            return $this->jsonp(array('error' => true, 'message' => 'not found'), $_GET['callback']);
        } else {
            return $this->jsonp(array(
                'error' => false,
                'value' => $cell->ans,
                'history' => array_values(CellHistory::search($values)->order('created DESC')->toArray())
            ), $_GET['callback']);
        }
    }

    public function getcellsAction()
    {
        list(, /*api*/, /*getcells*/, $page) = explode('/', $this->getURI());

        $values = array();
        if ($page != null) {
            $values = array('page' => intval($page));
        }

        $cells = Cell::search($values)->order('page, x, y ASC');
        $json = array();
        foreach ($cells as $cell) {
            array_push($json, array(
                'page' => $cell->page,
                'x' => $cell->x,
                'y' => $cell->y,
                'ans' => $cell->ans
            ));
        }
        return $this->jsonp($json, $_GET['callback']);
    }

    public function getcellcountAction()
    {
        return $this->jsonp(array('count' => count(Cell::search(1))), $_GET['callback']);
    }

    protected function getrandom()
    {
        $page = rand(1, 2500);
        if (rand(1, 100) > 50) {
            $promotions = array_values(PagePromotion::search(1)->toArray());
            $index = rand(0, count($promotions) - 1);
            $page = $promotions[$index]['page'];
        }
        $x = rand(2, 21);
        $y = rand(2, 7);

        $ans = "";

        $cell = Cell::search(array('page' => $page, 'x' => $x, 'y' => $y))->first();
        if ($cell != NULL) {
            if (rand(1, 100) < 80) {
                return $this->getrandom();
            }
            $ans = $cell->ans;
        }

        return array($page, $x, $y, $ans);
    }

    public function getrandomAction()
    {
        list($page, $x, $y, $ans) = $this->getrandom();

        $api_url = "http://" . strval(getenv(CAMPAIGN_FINANCE_RONNY)) . "/api/getcellimage";
        $img_url = $api_url . "/" . $page . "/" . $x . "/" . $y . ".png";

        return $this->json(array(
            'img_url' => $img_url,
            'page' => $page,
            'x' => $x,
            'y' => $y,
            'ans' => $ans
        ));
    }
}
